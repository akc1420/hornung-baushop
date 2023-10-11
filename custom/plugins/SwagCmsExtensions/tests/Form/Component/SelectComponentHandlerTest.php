<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Form\Component;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\DataAbstractionLayer\Validation\EntityExists;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Swag\CmsExtensions\Extension\Feature\FormBuilder\CmsSlotEntityExtension;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Component\Exception\UnsupportedSelectEntityException;
use Swag\CmsExtensions\Form\Component\Handler\SelectComponentHandler;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Service\Content\Cms\DataResolver\CustomFormCmsElementResolver;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\AtLeastOneOf;
use Symfony\Component\Validator\Constraints\Blank;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class SelectComponentHandlerTest extends AbstractComponentHandlerTest
{
    use SalesChannelFunctionalTestBehaviour;

    /**
     * @var CustomFormCmsElementResolver
     */
    private $resolver;

    /**
     * @var EntityRepositoryInterface
     */
    private $formRepository;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var EntityRepositoryInterface
     */
    private $formGroupRepository;

    public function setUp(): void
    {
        parent::setUp();
        $container = $this->getContainer();

        $resolver = $container->get(CustomFormCmsElementResolver::class);
        $formRepository = $this->getContainer()->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $formGroupRepository = $this->getContainer()->get(\sprintf('%s.repository', FormGroupDefinition::ENTITY_NAME));

        static::assertInstanceOf(CustomFormCmsElementResolver::class, $resolver);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formGroupRepository);

        $this->resolver = $resolver;
        $this->context = Context::createDefaultContext();
        $this->formRepository = $formRepository;
        $this->formGroupRepository = $formGroupRepository;
    }

    public function testPrepareStorefrontUnsupportedEntity(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $groupEntity = $groups->first();
        static::assertNotNull($groupEntity);

        $formGroupFieldCollection = $groupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $field->setConfig(['entity' => 'foo']);
        $field->addTranslated('config', ['entity' => 'foo']);

        $this->expectException(UnsupportedSelectEntityException::class);
        $this->handler->prepareStorefront($form, $field, $this->createSalesChannelContext());
    }

    public function testPrepareStorefrontSalutation(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->first();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $this->handler->prepareStorefront($form, $field, $this->createSalesChannelContext());

        $config = $field->getTranslation('config');
        static::assertNotNull($config);
        static::assertArrayHasKey('options', $config);
        static::assertNotEmpty($config['options']);
    }

    public function testPrepareStorefrontCountry(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->last();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $field->setConfig(\array_merge($field->getConfig() ?? [], ['options' => ['exists' => 'exists']]));
        $field->addTranslated('config', \array_merge($field->getTranslation('config') ?? [], ['options' => ['exists' => 'exists']]));

        $config = $field->getTranslation('config');
        static::assertNotNull($config);
        static::assertArrayHasKey('options', $config);

        $this->handler->prepareStorefront($form, $field, $this->createSalesChannelContext());

        $config = $field->getTranslation('config');
        static::assertNotNull($config);
        static::assertArrayHasKey('options', $config);
        static::assertNotEmpty($config['options']);
        static::assertNotCount(1, $config['options']);
        static::assertArrayHasKey('exists', $config['options']);
    }

    public function testPrepareStorefrontCustom(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->last();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->first();
        static::assertNotNull($field);

        $config = $field->getTranslation('config');
        static::assertNotNull($config);
        static::assertArrayHasKey('options', $config);

        $this->handler->prepareStorefront($form, $field, $this->createSalesChannelContext());

        $config = $field->getTranslation('config');
        static::assertNotNull($config);
        static::assertArrayHasKey('options', $config);
        static::assertNotEmpty($config['options']);
        static::assertCount(2, $config['options']);
        static::assertArrayHasKey('good', $config['options']);
        static::assertArrayHasKey('bad', $config['options']);
    }

    public function testPrepareStorefrontSalutationWithResolver(): void
    {
        $form = $this->createForm();
        $slot = $this->createCmsSlot($form);

        $criteria = new Criteria();
        $criteria->addAssociation('fields');
        $criteria->addFilter(new EqualsFilter('formId', $form->getId()));
        $criteria->addSorting(new FieldSorting('position'));
        $criteria->getAssociation('fields')->addSorting(new FieldSorting('position'));

        $dataCollection = new ElementDataCollection();
        $dataCollection->add('formgroups_' . $slot->getUniqueIdentifier(), $this->formGroupRepository->search($criteria, $this->context));

        $this->resolver->enrich($slot, new ResolverContext($this->createSalesChannelContext(), new Request()), $dataCollection);

        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->first();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $config = $field->getTranslation('config');
        static::assertNotNull($config);
        static::assertArrayHasKey('options', $config);
        static::assertNotEmpty($config['options']);
    }

    public function testPrepareStorefrontCountryWithResolver(): void
    {
        $form = $this->createForm();
        $slot = $this->createCmsSlot($form);
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->last();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $field->setConfig(\array_merge($field->getConfig() ?? [], ['options' => ['exists' => 'exists']]));
        $field->addTranslated('config', \array_merge($field->getTranslation('config') ?? [], ['options' => ['exists' => 'exists']]));

        $criteria = new Criteria();
        $criteria->addAssociation('fields');
        $criteria->addFilter(new EqualsFilter('formId', $form->getId()));
        $criteria->addSorting(new FieldSorting('position'));
        $criteria->getAssociation('fields')->addSorting(new FieldSorting('position'));

        $dataCollection = new ElementDataCollection();
        $dataCollection->add('formgroups_' . $slot->getUniqueIdentifier(), $this->formGroupRepository->search($criteria, $this->context));

        $this->resolver->enrich($slot, new ResolverContext($this->createSalesChannelContext(), new Request()), $dataCollection);

        static::assertNotNull($form->getGroups());
        static::assertCount(2, $form->getGroups());

        $config = $field->getTranslation('config');
        static::assertNotNull($config);
        static::assertArrayHasKey('options', $config);
        static::assertNotEmpty($config['options']);
        static::assertArrayHasKey('exists', $config['options']);
    }

    public function testRenderSalutation(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->first();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $salutationId = $this->getValidSalutationId();
        $formData = new RequestDataBag(['selectSalutationField' => $salutationId]);

        $result = $this->handler->render($field, $formData, $this->createSalesChannelContext());
        static::assertNotEquals($salutationId, $result);
    }

    public function testRenderCountry(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->last();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $countryId = $this->getValidCountryId();
        $formData = new RequestDataBag(['selectCountryField' => $countryId]);

        $result = $this->handler->render($field, $formData, $this->createSalesChannelContext());
        static::assertNotEquals($countryId, $result);
    }

    public function testRenderCountryNotExisting(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->last();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $countryId = Uuid::randomHex();
        $formData = new RequestDataBag(['selectCountryField' => $countryId]);

        $result = $this->handler->render($field, $formData, $this->createSalesChannelContext());
        static::assertEquals($countryId, $result);
    }

    public function testRenderCustomValue(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->last();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $value = 'aValue';
        $formData = new RequestDataBag(['selectCountryField' => $value]);

        $result = $this->handler->render($field, $formData, $this->createSalesChannelContext());
        static::assertEquals($value, $result);
    }

    public function testRenderUnknownEntity(): void
    {
        $form = $this->createForm();
        $groups = $form->getGroups();
        static::assertNotNull($groups);

        $formGroupEntity = $groups->last();
        static::assertNotNull($formGroupEntity);

        $formGroupFieldCollection = $formGroupEntity->getFields();
        static::assertNotNull($formGroupFieldCollection);

        $field = $formGroupFieldCollection->last();
        static::assertNotNull($field);

        $field->setConfig(['entity' => 'foo']);
        $field->addTranslated('config', ['entity' => 'foo']);

        $randomId = Uuid::randomHex();
        $formData = new RequestDataBag(['selectCountryField' => $randomId]);

        $this->expectException(UnsupportedSelectEntityException::class);
        $this->handler->render($field, $formData, $this->createSalesChannelContext());
    }

    /**
     * @return array<array{0: bool, string|null, array<string, string>|null}>
     */
    public function dataProviderValidation(): array
    {
        return [
            [true, 'salutation', ['a' => 'a', 'b' => 'b']],
            [true, 'salutation', ['keyA' => 'a', 'keyB' => 'b']],
            [true, null, ['a' => 'a', 'b' => 'b']],
            [true, null, ['keyA' => 'a', 'keyB' => 'b']],
            [true, 'salutation', null],
            [true, null, null],
            [false, 'salutation', ['a' => 'a', 'b' => 'b']],
            [false, 'salutation', ['keyA' => 'a', 'keyB' => 'b']],
            [false, null, ['a' => 'a', 'b' => 'b']],
            [false, null, ['keyA' => 'a', 'keyB' => 'b']],
            [false, 'salutation', null],
            [false, null, null],
        ];
    }

    /**
     * @dataProvider dataProviderValidation
     *
     * @param array<string, string>|null $options
     */
    public function testGetValidation(bool $required, ?string $entity, ?array $options): void
    {
        $definition = $this->handler->getValidationDefinition(
            $this->createField($this->getType(), $required, ['entity' => $entity, 'options' => $options]),
            $this->createSalesChannelContext()
        );

        if ($entity === null && $options === null) {
            static::assertCount(2, $definition);
            static::assertInstanceOf(Blank::class, $definition[0]);
            static::assertInstanceOf(NotBlank::class, $definition[1]);

            return;
        }

        static::assertCount(($required ? 1 : 0) + 1, $definition);
        if ($required) {
            static::assertInstanceOf(NotBlank::class, $definition[0]);
        }

        $mainDefinition = $definition[$required ? 1 : 0];
        if ($entity !== null && $options !== null) {
            static::assertInstanceOf(AtLeastOneOf::class, $mainDefinition);
            static::assertInstanceOf(AtLeastOneOf::class, $mainDefinition->constraints[0]);

            /** @var Choice[] $atLeastOneOfConstraints */
            $atLeastOneOfConstraints = $mainDefinition->constraints[0]->getNestedConstraints();
            static::assertEqualsCanonicalizing(\array_keys($options), $atLeastOneOfConstraints[0]->choices);
            static::assertEqualsCanonicalizing(\array_values($options), $atLeastOneOfConstraints[1]->choices);
            static::assertInstanceOf(EntityExists::class, $mainDefinition->constraints[1]);
            static::assertSame($entity, $mainDefinition->constraints[1]->entity);
        } elseif ($entity !== null) {
            static::assertInstanceOf(EntityExists::class, $mainDefinition);
            static::assertSame($entity, $mainDefinition->entity);
        } elseif ($options !== null) {
            static::assertInstanceOf(AtLeastOneOf::class, $mainDefinition);

            /** @var Choice[] $atLeastOneOfConstraints */
            $atLeastOneOfConstraints = $mainDefinition->getNestedConstraints();
            static::assertEqualsCanonicalizing(\array_keys($options), $atLeastOneOfConstraints[0]->choices);
            static::assertEqualsCanonicalizing(\array_values($options), $atLeastOneOfConstraints[1]->choices);
        }
    }

    protected function getType(): string
    {
        return 'select';
    }

    protected function getHandlerClass(): string
    {
        return SelectComponentHandler::class;
    }

    private function createCmsSlot(?FormEntity $formEntity = null): CmsSlotEntity
    {
        $slot = new CmsSlotEntity();
        $slot->setType('custom-form');
        $slot->setId(Uuid::randomHex());
        if ($formEntity !== null) {
            $slot->addExtension(CmsSlotEntityExtension::FORM_ASSOCIATION_PROPERTY_NAME, $formEntity);
        }

        return $slot;
    }

    private function createForm(bool $loadAll = true): FormEntity
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
                        ],
                    ],
                    [
                        'position' => 1,
                        'technicalName' => 'row2',
                        'fields' => [
                            [
                                'position' => 1,
                                'width' => 4,
                                'type' => 'select',
                                'required' => true,
                                'technicalName' => 'selectCountryField',
                                'label' => 'Country',
                                'config' => [
                                    'entity' => CountryDefinition::ENTITY_NAME,
                                ],
                            ],
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
        ], $this->context);

        $criteria = new Criteria([$id]);
        if ($loadAll) {
            $criteria->addAssociation('groups.fields');
            $criteria->getAssociation('groups')->addSorting(new FieldSorting('position'))
                ->getAssociation('fields')->addSorting(new FieldSorting('position'));
        }

        /** @var FormEntity|null $form */
        $form = $this->formRepository->search($criteria, $this->context)->first();

        static::assertNotNull($form);

        return $form;
    }
}
