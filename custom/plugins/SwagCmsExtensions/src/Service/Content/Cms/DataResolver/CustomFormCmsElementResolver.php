<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Service\Content\Cms\DataResolver;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Swag\CmsExtensions\Extension\Feature\FormBuilder\CmsSlotEntityExtension;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupDefinition;
use Swag\CmsExtensions\Form\Component\ComponentRegistry;
use Swag\CmsExtensions\Form\FormEntity;

class CustomFormCmsElementResolver extends AbstractCmsElementResolver
{
    public const CUSTOM_FORM_TYPE = 'custom-form';

    /**
     * @var ComponentRegistry
     */
    protected $componentRegistry;

    public function __construct(ComponentRegistry $componentRegistry)
    {
        $this->componentRegistry = $componentRegistry;
    }

    public function getType(): string
    {
        return self::CUSTOM_FORM_TYPE;
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        /** @var FormEntity|null $form */
        $form = $slot->getExtension(CmsSlotEntityExtension::FORM_ASSOCIATION_PROPERTY_NAME);

        if ($form === null) {
            return null;
        }

        $criteriaCollection = new CriteriaCollection();

        $criteria = new Criteria();
        $criteria->addAssociation('fields');
        $criteria->addFilter(new EqualsFilter('formId', $form->getId()));
        $criteria->addSorting(new FieldSorting('position'));
        $criteria->getAssociation('fields')->addSorting(new FieldSorting('position'));

        $criteriaCollection->add('formgroups_' . $slot->getUniqueIdentifier(), FormGroupDefinition::class, $criteria);

        return $criteriaCollection;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $groupData = $result->get('formgroups_' . $slot->getUniqueIdentifier());

        if ($groupData === null) {
            return;
        }

        /** @var FormEntity|null $form */
        $form = $slot->getExtension(CmsSlotEntityExtension::FORM_ASSOCIATION_PROPERTY_NAME);

        if ($form === null) {
            return;
        }

        /** @var FormGroupCollection $groups */
        $groups = $groupData->getEntities();

        $form->setGroups($groups);

        foreach ($groups->getFields() as $field) {
            $handler = $this->componentRegistry->getHandler($field->getType());
            $handler->prepareStorefront($form, $field, $resolverContext->getSalesChannelContext());
        }
    }
}
