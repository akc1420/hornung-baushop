<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Swag\CmsExtensions\ScrollNavigation\ScrollNavigationDefinition;

class ScrollNavigationTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_scroll_navigation_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return ScrollNavigationTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return ScrollNavigationDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            new StringField('display_name', 'displayName'),
        ]);
    }
}
