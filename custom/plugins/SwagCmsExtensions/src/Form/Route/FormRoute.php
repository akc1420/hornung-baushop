<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Route;

use OpenApi\Annotations as OA;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Captcha\Annotation\Captcha;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldCollection;
use Swag\CmsExtensions\Form\Component\ComponentRegistry;
use Swag\CmsExtensions\Form\Event\CustomFormEvent;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Form\Route\Exception\InvalidFormIdException;
use Swag\CmsExtensions\Form\Route\Validation\FormValidationFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @Route(defaults={"_routeScope"={"store-api"}})
 */
class FormRoute extends AbstractFormRoute
{
    private FormValidationFactory $formValidationFactory;

    private DataValidator $validator;

    private EventDispatcherInterface $eventDispatcher;

    private EntityRepository $formRepository;

    private ComponentRegistry $componentRegistry;

    public function __construct(
        FormValidationFactory $formValidationFactory,
        DataValidator $validator,
        EventDispatcherInterface $eventDispatcher,
        EntityRepository $formRepository,
        ComponentRegistry $componentRegistry
    ) {
        $this->formValidationFactory = $formValidationFactory;
        $this->validator = $validator;
        $this->eventDispatcher = $eventDispatcher;
        $this->formRepository = $formRepository;
        $this->componentRegistry = $componentRegistry;
    }

    public function getDecorated(): AbstractFormRoute
    {
        throw new DecorationPatternException(self::class);
    }

    /**
     * @OA\Post(
     *     path="/swag/cms-extensions/form",
     *     summary="Send message through form",
     *     operationId="sendCustomFormMail",
     *     tags={"Store API", "Form Mail"},
     *     @OA\RequestBody(
     *         required=true,
     *         description="The entered form values and reference to the concerning form",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="formId",
     *                 description="The form id this mailing is for",
     *                 type="string",
     *                 format="uuid"
     *             ),
     *             additionalProperties=true,
     *             example={"formId": "19489f5e16e14ac8b7c1dad26a258923", "checkboxField": 1, "textField": "Lorem ipsum"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Message sent"
     *     )
     * )
     *
     * @Route("/store-api/swag/cms-extensions/form", name="store-api.swag.cms-extensions.form", methods={"POST"}, defaults={"_captcha"=true})
     */
    public function send(RequestDataBag $formData, SalesChannelContext $context): FormRouteResponse
    {
        $form = $this->loadForm($formData, $context);
        $this->validateForm($form, $formData, $context);
        $this->cleanData($form, $formData);
        $renderedData = $this->renderData($form, $formData, $context);
        $this->eventDispatcher->dispatch(new CustomFormEvent($context, $form, $renderedData), CustomFormEvent::EVENT_NAME);

        $response = new FormRouteResponseStruct();
        $response->assign([
            'successMessage' => $form->getTranslation('successMessage') ?? '',
        ]);

        return new FormRouteResponse($response);
    }

    private function validateForm(FormEntity $form, DataBag $data, SalesChannelContext $context): void
    {
        $definition = $this->formValidationFactory->create($form, $data, $context);
        $violations = $this->validator->getViolations($data->all(), $definition);

        if ($violations->count() > 0) {
            throw new ConstraintViolationException($violations, $data->all());
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function renderData(FormEntity $form, RequestDataBag $formData, SalesChannelContext $context): array
    {
        $groups = $form->getGroups();
        $fields = $groups === null ? new FormGroupFieldCollection() : $groups->getFields();
        $renderedData = [];

        foreach ($fields as $field) {
            $handler = $this->componentRegistry->getHandler($field->getType());
            $data = $handler->render($field, $formData, $context);

            if ($data !== null) {
                $renderedData[$field->getTechnicalName()] = $data;
            }
        }

        return $renderedData;
    }

    private function loadForm(RequestDataBag $formData, SalesChannelContext $context): FormEntity
    {
        $formId = $this->getFormId($formData);
        $formData->set('formId', $formId);

        $criteria = new Criteria([$formId]);
        $criteria->addAssociation('groups.fields');
        $criteria->addAssociation('mailTemplate');
        $criteria->getAssociation('groups')
            ->addSorting(new FieldSorting('position'))
            ->getAssociation('fields')
            ->addSorting(new FieldSorting('position'));

        /** @var FormEntity|null $form */
        $form = $this->formRepository->search($criteria, $context->getContext())->getEntities()->first();

        if ($form === null) {
            throw new InvalidFormIdException($formId);
        }

        return $form;
    }

    private function cleanData(FormEntity $form, RequestDataBag $formData): void
    {
        foreach ($formData->keys() as $key) {
            $groups = $form->getGroups();
            $fields = $groups === null ? new FormGroupFieldCollection() : $groups->getFields();

            if ($fields->getFieldByTechnicalName($key) === null) {
                $formData->remove($key);
            }
        }
    }

    private function getFormId(RequestDataBag $formData): string
    {
        $formId = \str_replace('form-', '', $formData->get('formId') ?? '');

        if (!Uuid::isValid($formId)) {
            throw new MissingRequestParameterException('formId');
        }

        return $formId;
    }
}
