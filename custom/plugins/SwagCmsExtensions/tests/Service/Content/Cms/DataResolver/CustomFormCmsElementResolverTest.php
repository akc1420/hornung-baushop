<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Test\Service\Content\Cms\DataResolver;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Test\TestCaseBase\SalesChannelFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Swag\CmsExtensions\Extension\Feature\FormBuilder\CmsSlotEntityExtension;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\FormDefinition;
use Swag\CmsExtensions\Form\FormEntity;
use Swag\CmsExtensions\Service\Content\Cms\DataResolver\CustomFormCmsElementResolver;
use Swag\CmsExtensions\Util\Lifecycle\FormDefaults;
use Symfony\Component\HttpFoundation\Request;

class CustomFormCmsElementResolverTest extends TestCase
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
        $formRepository = $container->get(\sprintf('%s.repository', FormDefinition::ENTITY_NAME));
        $formGroupRepository = $container->get(\sprintf('%s.repository', FormGroupDefinition::ENTITY_NAME));

        static::assertInstanceOf(CustomFormCmsElementResolver::class, $resolver);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formRepository);
        static::assertInstanceOf(EntityRepositoryInterface::class, $formGroupRepository);

        $this->resolver = $resolver;
        $this->context = Context::createDefaultContext();
        $this->formRepository = $formRepository;
        $this->formGroupRepository = $formGroupRepository;
    }

    public function testGetType(): void
    {
        static::assertSame(CustomFormCmsElementResolver::CUSTOM_FORM_TYPE, $this->resolver->getType());
    }

    public function testCollect(): void
    {
        $form = $this->createForm();
        $slot = $this->createCmsSlot($form);

        $criteriaCollection = $this->resolver->collect($slot, new ResolverContext($this->createSalesChannelContext(), new Request()));

        static::assertNotNull($criteriaCollection);

        $criterias = $criteriaCollection->all();
        static::assertCount(1, $criterias);

        foreach ($criterias as $definition => $criteriaSubset) {
            static::assertSame($definition, FormGroupDefinition::class);
            static::assertCount(1, $criteriaSubset);
            foreach ($criteriaSubset as $identifier => $criteria) {
                static::assertSame('formgroups_' . $slot->getUniqueIdentifier(), $identifier);
                static::assertTrue($criteria->hasEqualsFilter('formId'));
                static::assertCount(1, $criteria->getSorting());
                static::assertTrue($criteria->hasAssociation('fields'));
                $association = $criteria->getAssociation('fields');
                static::assertCount(1, $association->getSorting());
            }
        }
    }

    public function testCollectWithoutForm(): void
    {
        $slot = $this->createCmsSlot();

        $criteriaCollection = $this->resolver->collect($slot, new ResolverContext($this->createSalesChannelContext(), new Request()));

        static::assertNull($criteriaCollection);
    }

    public function testEnrich(): void
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

        static::assertNotNull($form->getGroups());
        static::assertCount(2, $form->getGroups());
    }

    public function testEnrichWithoutForm(): void
    {
        $form = $this->createForm();
        $slot = $this->createCmsSlot();

        $criteria = new Criteria();
        $criteria->addAssociation('fields');
        $criteria->addFilter(new EqualsFilter('formId', $form->getId()));
        $criteria->addSorting(new FieldSorting('position'));
        $criteria->getAssociation('fields')->addSorting(new FieldSorting('position'));

        $dataCollection = new ElementDataCollection();
        $dataCollection->add('formgroups_' . $slot->getUniqueIdentifier(), $this->formGroupRepository->search($criteria, $this->context));

        $this->resolver->enrich($slot, new ResolverContext($this->createSalesChannelContext(), new Request()), $dataCollection);

        static::assertNull($form->getGroups());
    }

    private function createCmsSlot(?FormEntity $formEntity = null): CmsSlotEntity
    {
        $slot = new CmsSlotEntity();
        $slot->setType(CustomFormCmsElementResolver::CUSTOM_FORM_TYPE);
        $slot->setId(Uuid::randomHex());
        if ($formEntity !== null) {
            $slot->addExtension(CmsSlotEntityExtension::FORM_ASSOCIATION_PROPERTY_NAME, $formEntity);
        }

        return $slot;
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
                                'type' => 'text',
                                'required' => true,
                                'technicalName' => 'textField',
                                'label' => 'Name',
                            ],
                            [
                                'position' => 1,
                                'width' => 6,
                                'type' => 'email',
                                'required' => true,
                                'technicalName' => 'emailField',
                                'label' => 'E-Mail',
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
                                'type' => 'number',
                                'required' => false,
                                'technicalName' => 'numberField',
                                'label' => 'Nummer',
                            ],
                            [
                                'position' => 0,
                                'width' => 8,
                                'type' => 'textarea',
                                'required' => true,
                                'technicalName' => 'textareaField',
                                'label' => 'Textfeld',
                                'config' => [
                                    'rows' => 3,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $this->context);

        /** @var FormEntity|null $form */
        $form = $this->formRepository->search(new Criteria([$id]), $this->context)->first();

        static::assertNotNull($form);

        return $form;
    }
}
