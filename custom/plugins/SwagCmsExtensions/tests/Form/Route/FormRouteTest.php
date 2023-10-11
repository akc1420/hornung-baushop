<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Route;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Shopware\Core\System\Salutation\SalutationEntity;
use Swag\CmsExtensions\Form\Component\ComponentRegistry;
use Swag\CmsExtensions\Form\Event\CustomFormEvent;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Form\Route\Exception\InvalidFormIdException;
use Swag\CmsExtensions\Form\Route\FormRoute;
use Swag\CmsExtensions\Form\Route\Validation\FormValidationFactory;
use Swag\CmsExtensions\Test\Mock\EventDispatcherMock;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;

class FormRouteTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @var FormRoute
     */
    private $formRoute;

    /**
     * @var EntityRepository
     */
    private $formRepository;

    /**
     * @var EventDispatcherMock
     */
    private $eventDispatcher;

    public function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $formRepository = $container->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $formValidation = $container->get(FormValidationFactory::class);
        $validator = $container->get(DataValidator::class);
        $componentRegistry = $container->get(ComponentRegistry::class);

        static::assertInstanceOf(EntityRepository::class, $formRepository);
        static::assertInstanceOf(FormValidationFactory::class, $formValidation);
        static::assertInstanceOf(DataValidator::class, $validator);
        static::assertInstanceOf(ComponentRegistry::class, $componentRegistry);

        $this->formRepository = $formRepository;
        $this->eventDispatcher = new EventDispatcherMock();
        $this->formRoute = new FormRoute(
            $formValidation,
            $validator,
            $this->eventDispatcher,
            $this->formRepository,
            $componentRegistry
        );
    }

    public function testSend(): void
    {
        $form = $this->createForm();

        $data = [
            'formId' => $form->getId(),
            'selectField' => 'good',
            'textField' => 'Demotext',
            'selectSalutationField' => $this->getValidSalutationId(),
            'unknownField' => 'delete this!',
        ];

        $salesChannelContext = $this->createSalesChannelContext();
        $result = $this->formRoute->send(new RequestDataBag($data), $salesChannelContext);
        static::assertSame($form->getSuccessMessage(), $result->getResult()->getSuccessMessage());
        static::assertSame('custom_form_result', $result->getResult()->getApiAlias());

        $events = $this->eventDispatcher->getSentEvents();
        static::assertCount(1, $events);

        $event = \current($events);
        static::assertInstanceOf(CustomFormEvent::class, $event);

        /** @var CustomFormEvent $event */
        static::assertSame(CustomFormEvent::EVENT_NAME, $event->getName());
        static::assertSame($form->getId(), $event->getForm()->getId());
        static::assertSame($salesChannelContext->getSalesChannelId(), $event->getSalesChannelId());
        static::assertSame($salesChannelContext->getSalesChannelId(), $event->getSalesChannelContext()->getSalesChannel()->getId());
        static::assertSame(CustomFormEvent::EVENT_NAME, $event->getName());

        // should be removed because not a field
        unset($data['unknownField'], $data['formId']);

        // salutation id is replaced by display name
        /** @var EntityRepositoryInterface $salutationRepository */
        $salutationRepository = $this->getContainer()->get(\sprintf('%s.repository', SalutationDefinition::ENTITY_NAME));
        /** @var SalutationEntity $salutation */
        $salutation = $salutationRepository->search(new Criteria([$this->getValidSalutationId()]), $event->getContext())->first();
        static::assertNotNull($salutation);
        $data['selectSalutationField'] = $salutation->getDisplayName();

        // should be sorted to the bottom
        unset($data['selectField']);
        $data['selectField'] = 'good';

        static::assertSame($data, $event->getFormData());
    }

    public function testSendValidationError(): void
    {
        $form = $this->createForm();

        $data = [
            'formId' => $form->getId(),
            'selectField' => 'notAvailableValue',
            'textField' => ['test'],
            'selectSalutationField' => Uuid::randomHex(),
            'numberField' => 9,
            'maxNumberField' => 9,
            'unknownField' => 'delete this!',
        ];

        $salesChannelContext = $this->createSalesChannelContext();
        $this->expectException(ConstraintViolationException::class);

        try {
            $this->formRoute->send(new RequestDataBag($data), $salesChannelContext);
        } catch (ConstraintViolationException $formViolations) {
            if (($_SERVER['CORE_BRANCH'] ?? '') === 'v6.4.0.0-RC1') {
                // broken Range-Validation on 6.4.0.0 RC1
                static::assertCount(4, $formViolations->getViolations());
            } else {
                static::assertCount(5, $formViolations->getViolations());
            }

            throw $formViolations;
        }
    }

    public function testSendNoFormId(): void
    {
        $this->expectException(MissingRequestParameterException::class);
        $this->formRoute->send(new RequestDataBag(), $this->createSalesChannelContext());

        $events = $this->eventDispatcher->getSentEvents();
        static::assertEmpty($events);
    }

    public function testSendInvalidFormId(): void
    {
        $data = [
            'formId' => Uuid::randomHex(),
        ];

        $this->expectException(InvalidFormIdException::class);
        $this->formRoute->send(new RequestDataBag($data), $this->createSalesChannelContext());

        $events = $this->eventDispatcher->getSentEvents();
        static::assertEmpty($events);
    }

    private function createForm(): FormEntity
    {
        $id = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $this->formRepository->upsert([
            [
                'id' => $id,
                'title' => 'Formtitel',
                'technicalName' => 'technical-form-name',
                'successMessage' => 'Success!',
                'mailTemplate' => [
                    'mailTemplateTypeId' => FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID,
                    'subject' => 'Subject',
                    'contentHtml' => '<p>Hello World!</p>',
                    'contentPlain' => 'Hello World!',
                ],
                'groups' => [
                    [
                        'position' => 0,
                        'technicalName' => 'row1',
                        'title' => 'Zeile 1',
                        'fields' => [
                            [
                                'position' => 0,
                                'width' => 6,
                                'type' => 'text',
                                'required' => true,
                                'technicalName' => 'textField',
                                'label' => 'Name',
                            ],
                            [
                                'position' => 1,
                                'width' => 6,
                                'type' => 'select',
                                'required' => true,
                                'technicalName' => 'selectSalutationField',
                                'label' => 'Salutation',
                                'config' => [
                                    'entity' => SalutationDefinition::ENTITY_NAME,
                                ],
                            ],
                            [
                                'position' => 0,
                                'width' => 6,
                                'type' => 'number',
                                'required' => false,
                                'technicalName' => 'numberField',
                                'label' => 'Name',
                                'config' => [
                                    'min' => 0,
                                    'max' => 6,
                                ],
                            ],
                            [
                                'position' => 0,
                                'width' => 6,
                                'type' => 'number',
                                'required' => false,
                                'placeholder' => 'will not be filled',
                                'technicalName' => 'minNumberField',
                                'label' => 'Name',
                                'config' => [
                                    'min' => 0,
                                ],
                            ],
                            [
                                'position' => 0,
                                'width' => 6,
                                'type' => 'number',
                                'required' => false,
                                'technicalName' => 'maxNumberField',
                                'label' => 'Name',
                                'config' => [
                                    'max' => 6,
                                ],
                            ],
                        ],
                    ],
                    [
                        'position' => 1,
                        'technicalName' => 'row2',
                        'fields' => [
                            [
                                'position' => 0,
                                'width' => 8,
                                'type' => 'select',
                                'required' => true,
                                'technicalName' => 'selectField',
                                'label' => 'How are you?',
                                'config' => [
                                    'options' => [
                                        'good' => 'good',
                                        'bad' => 'bad',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $context);

        /** @var FormEntity|null $form */
        $form = $this->formRepository->search(new Criteria([$id]), $context)->first();

        static::assertNotNull($form);

        return $form;
    }
}
