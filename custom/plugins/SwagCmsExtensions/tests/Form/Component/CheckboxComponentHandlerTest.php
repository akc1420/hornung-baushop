<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Component;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Swag\CmsExtensions\Form\Component\Handler\CheckboxComponentHandler;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckboxComponentHandlerTest extends AbstractComponentHandlerTest
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $this->context = Context::createDefaultContext();
        $formRepository = $container->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $translator = $container->get('translator');

        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        static::assertInstanceOf(DataCollectorTranslator::class, $translator);

        $this->formRepository = $formRepository;
        $this->translator = $translator;
    }

    /**
     * @dataProvider dataProviderRender
     *
     * @param int|string|bool|null $actual
     */
    public function testRender($actual, bool $expected): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->first();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->first();
        static::assertNotNull($field);

        $formData = new RequestDataBag(['checkboxField' => $actual]);

        $result = $this->handler->render($field, $formData, $this->createSalesChannelContext());
        static::assertEquals($this->translator->trans(\sprintf('swagCmsExtensions.form.component.checkbox.%s', $expected ? 'true' : 'false')), $result);
    }

    /**
     * @return array<int, array{0: bool|string|int|null, 1: bool}>
     */
    public function dataProviderRender(): array
    {
        return [
            [true, true],
            [false, false],
            [null, false],
            ['', false],
            ['1', true],
            ['0', false],
            ['true', true],
            ['false', false],
            [1, true],
            [0, false],
        ];
    }

    /**
     * @dataProvider dataProviderValidation
     */
    public function testGetValidation(bool $required): void
    {
        $definition = $this->handler->getValidationDefinition(
            $this->createField($this->getType(), $required),
            $this->createSalesChannelContext()
        );

        static::assertCount($required ? 1 : 0, $definition);
        if ($required) {
            static::assertInstanceOf(NotBlank::class, $definition[0]);
        }
    }

    protected function getType(): string
    {
        return 'checkbox';
    }

    protected function getHandlerClass(): string
    {
        return CheckboxComponentHandler::class;
    }

    private function createForm(): FormEntity
    {
        $id = Uuid::randomHex();
        $this->formRepository->upsert([
            [
                'id' => $id,
                'title' => 'Formtitel',
                'technicalName' => 'technical-form-name',
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
                                'type' => $this->getType(),
                                'required' => true,
                                'technicalName' => 'checkboxField',
                                'label' => 'Name',
                            ],
                        ],
                    ],
                ],
            ],
        ], $this->context);

        $criteria = new Criteria([$id]);
        $criteria
            ->addAssociation('groups.fields')
            ->getAssociation('groups')
            ->addSorting(new FieldSorting('position'))
            ->getAssociation('fields')
            ->addSorting(new FieldSorting('position'));

        /** @var FormEntity|null $form */
        $form = $this->formRepository->search($criteria, $this->context)->first();

        static::assertNotNull($form);

        return $form;
    }
}
