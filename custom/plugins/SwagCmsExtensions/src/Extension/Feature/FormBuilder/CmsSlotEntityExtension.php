<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Extension\Feature\FormBuilder;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\CascadeDelete;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\Form\FormDefinition;

class CmsSlotEntityExtension extends EntityExtension
{
    public const FORM_ASSOCIATION_PROPERTY_NAME = 'swagCmsExtensionsForm';

    public function getDefinitionClass(): string
    {
        return CmsSlotDefinition::class;
    }

    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new OneToOneAssociationField(
                self::FORM_ASSOCIATION_PROPERTY_NAME,
                'id',
                FormDefinition::CMS_SLOT_FOREIGN_KEY_STORAGE_NAME,
                FormDefinition::class,
                true
            ))->addFlags(new CascadeDelete())
        );
    }
}
