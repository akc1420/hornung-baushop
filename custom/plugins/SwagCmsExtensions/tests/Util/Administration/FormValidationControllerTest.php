<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Util\Administration;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\MailTemplate\MailTemplateDefinition;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Util\Administration\FormValidationController;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FormValidationControllerTest extends TestCase
{
    use KernelTestBehaviour;

    /**
     * @var FormValidationController
     */
    private $formValidationController;

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $mailTemplateRepository;

    public function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $mailTemplateRepository = $container->get(\sprintf('%s.repository', MailTemplateDefinition::ENTITY_NAME));
        $formRepository = $container->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $formValidationController = $container->get(FormValidationController::class);

        static::assertInstanceOf(EntityRepositoryInterface::class, $mailTemplateRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        static::assertInstanceOf(FormValidationController::class, $formValidationController);

        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->formRepository = $formRepository;
        $this->formValidationController = $formValidationController;
    }

    public function testValidateForm(): void
    {
        $context = Context::createDefaultContext();
        $form = $this->createFormArray($context);
        $encodedForm = \json_encode($form);
        static::assertIsString($encodedForm);

        $request = new Request([], [], [], [], [], [], $encodedForm);

        $response = $this->formValidationController->validateForm($request, $context);

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertNull($this->formRepository->searchIds(new Criteria([$form['id']]), $context)->firstId());
    }

    public function testValidateFormWithFieldErrors(): void
    {
        $context = Context::createDefaultContext();
        $form = $this->createFormArray($context);
        $form['groups'][0]['fields'][0]['type'] = 'foo';
        $form['groups'][0]['fields'][0]['config'] = ['foo' => 'bar'];
        $encodedForm = \json_encode($form);
        static::assertIsString($encodedForm);

        $request = new Request([], [], [], [], [], [], $encodedForm);

        $this->expectException(WriteException::class);
        $this->formValidationController->validateForm($request, $context);
    }

    public function testValidateFormWithRegularErrors(): void
    {
        $context = Context::createDefaultContext();
        $form = $this->createFormArray($context);
        $form['groups'][0]['technicalName'] = 4.5;
        $encodedForm = \json_encode($form);
        static::assertIsString($encodedForm);

        $request = new Request([], [], [], [], [], [], $encodedForm);

        $this->expectException(WriteException::class);
        $this->formValidationController->validateForm($request, $context);
    }

    public function testValidateAllForms(): void
    {
        $context = Context::createDefaultContext();
        $form1 = $this->createFormArray($context);
        $form2 = $this->createFormArray($context);
        $form2['technicalName'] = 'other-name';
        $encodedForms = \json_encode([$form1, $form2]);
        static::assertIsString($encodedForms);

        $request = new Request([], [], [], [], [], [], $encodedForms);

        $response = $this->formValidationController->validateAllForms($request, $context);

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertNull($this->formRepository->searchIds(new Criteria([$form1['id'], $form2['id']]), $context)->firstId());
    }

    public function testValidateAllFormsWithDuplicateTechnicalNames(): void
    {
        $context = Context::createDefaultContext();
        $form1 = $this->createFormArray($context);
        $form2 = $this->createFormArray($context);
        $encodedForms = \json_encode([$form1, $form2]);
        static::assertIsString($encodedForms);

        $request = new Request([], [], [], [], [], [], $encodedForms);

        $this->expectException(WriteException::class);
        $this->formValidationController->validateAllForms($request, $context);
    }

    /**
     * @return array<string, mixed>
     */
    private function createFormArray(Context $context): array
    {
        return [
            'id' => Uuid::randomHex(),
            'title' => 'Formtitel',
            'isTemplate' => false,
            'technicalName' => 'technical-form-name',
            'successMessage' => 'Success!',
            'mailTemplateId' => $this->getValidMailTemplateId(FormDefaults::FORM_MAIL_TEMPLATE_TYPE_ID, $context),
            'groups' => [
                [
                    'position' => 0,
                    'technicalName' => 'row1',
                    'title' => 'Zeile 1',
                    'fields' => [
                        [
                            'position' => 1,
                            'width' => 6,
                            'type' => 'select',
                            'required' => true,
                            'technicalName' => 'selectSalutationField',
                            'label' => 'Salutation',
                            'config' => ['entity' => SalutationDefinition::ENTITY_NAME],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function getValidMailTemplateId(string $mailTemplateTypeId, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateTypeId', $mailTemplateTypeId));
        $criteria->addSorting(new FieldSorting('systemDefault', FieldSorting::DESCENDING));
        $criteria->setLimit(1);

        return $this->mailTemplateRepository->searchIds($criteria, $context)->firstId();
    }
}
