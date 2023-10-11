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
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\DataValidator;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Swag\CmsExtensions\Form\Component\ComponentRegistry;
use Swag\CmsExtensions\Form\Event\CustomFormEvent;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Form\Route\FormRoute;
use Swag\CmsExtensions\Form\Route\Validation\FormValidationFactory;
use Swag\CmsExtensions\Storefront\Controller\CustomFormController;
use Swag\CmsExtensions\Test\Mock\EventDispatcherMock;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;
use Symfony\Component\Translation\DataCollectorTranslator;

class CustomFormControllerTest extends TestCase
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @var CustomFormController
     */
    private $formController;

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
        $this->formController = new CustomFormController(new FormRoute(
            $formValidation,
            $validator,
            $this->eventDispatcher,
            $this->formRepository,
            $componentRegistry
        ));
        $this->formController->setContainer($this->getContainer());
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
        $jsonResult = $this->formController->sendForm(new RequestDataBag($data), $salesChannelContext);
        static::assertIsString($jsonResult->getContent());
        $result = \json_decode($jsonResult->getContent(), true);
        static::assertCount(1, $result);
        static::assertSame([
            'type' => 'success',
            'alert' => $form->getSuccessMessage(),
        ], $result[0]);

        $events = $this->eventDispatcher->getSentEvents();
        static::assertCount(1, $events);

        $event = \current($events);
        static::assertInstanceOf(CustomFormEvent::class, $event);
    }

    public function testSendWithNoSuccessMessage(): void
    {
        $form = $this->createForm(false);
        $translator = $this->getContainer()->get('translator');
        static::assertInstanceOf(DataCollectorTranslator::class, $translator);

        $data = [
            'formId' => $form->getId(),
            'selectField' => 'good',
            'textField' => 'Demotext',
            'selectSalutationField' => $this->getValidSalutationId(),
            'unknownField' => 'delete this!',
        ];

        $salesChannelContext = $this->createSalesChannelContext();
        $jsonResult = $this->formController->sendForm(new RequestDataBag($data), $salesChannelContext);
        static::assertIsString($jsonResult->getContent());
        $result = \json_decode($jsonResult->getContent(), true);
        static::assertCount(1, $result);
        static::assertSame([
            'type' => 'success',
            'alert' => $translator->trans('contact.success'),
        ], $result[0]);

        $events = $this->eventDispatcher->getSentEvents();
        static::assertCount(1, $events);

        $event = \current($events);
        static::assertInstanceOf(CustomFormEvent::class, $event);
    }

    public function testSendWithValidationErrors(): void
    {
        $form = $this->createForm(false);

        $data = [
            'formId' => $form->getId(),
            'selectField' => 'good',
            'textField' => 'Demotext',
            'missingEmailField' => 'foobar',
            'selectSalutationField' => Uuid::randomHex(),
            'unknownField' => 'delete this!',
        ];

        $salesChannelContext = $this->createSalesChannelContext();
        $jsonResult = $this->formController->sendForm(new RequestDataBag($data), $salesChannelContext);
        static::assertIsString($jsonResult->getContent());
        $result = \json_decode($jsonResult->getContent(), true);
        static::assertCount(1, $result);
        static::assertSame('danger', $result[0]['type']);
        static::assertNotEmpty($result[0]['alert']);
        static::assertSame(2, \mb_substr_count($result[0]['alert'], '<li>'));
        static::assertStringContainsString(\sprintf('"%s" does not exist', $data['selectSalutationField']), $result[0]['alert']);

        $events = $this->eventDispatcher->getSentEvents();
        static::assertEmpty($events);
    }

    private function createForm(bool $setSuccessMessage = true): FormEntity
    {
        $id = Uuid::randomHex();
        $context = Context::createDefaultContext();

        $this->formRepository->upsert([
            [
                'id' => $id,
                'title' => 'Formtitel',
                'technicalName' => 'technical-form-name',
                'successMessage' => $setSuccessMessage ? 'Success!' : null,
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
                                'type' => 'email',
                                'required' => false,
                                'placeholder' => 'will not be filled',
                                'technicalName' => 'missingEmailField',
                                'label' => 'Name',
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
