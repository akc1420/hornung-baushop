<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\BlockRule;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockDefinition;
use Shopware\Core\Content\Rule\RuleDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

class BlockRuleDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'swag_cms_extensions_block_rule';
    public const CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME = CmsBlockDefinition::ENTITY_NAME . '_id';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    /**
     * @return array<string, bool>
     */
    public function getDefaults(): array
    {
        return [
            'inverted' => false,
        ];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new PrimaryKey(), new Required(), new ApiAware()),
            (new BoolField('inverted', 'inverted'))->addFlags(new ApiAware()),
            (new FkField('visibility_rule_id', 'visibilityRuleId', RuleDefinition::class))->addFlags(new ApiAware()),
            (new FkField(self::CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME, 'cmsBlockId', CmsBlockDefinition::class))->addFlags(new Required(), new ApiAware()),
            (new ReferenceVersionField(CmsBlockDefinition::class))->addFlags(new Required(), new ApiAware()),

            (new ManyToOneAssociationField(
                'visibilityRule',
                'visibility_rule_id',
                RuleDefinition::class,
                'id',
                false
            ))->addFlags(new ApiAware()),
            (new OneToOneAssociationField(
                'cmsBlock',
                self::CMS_BLOCK_FOREIGN_KEY_STORAGE_NAME,
                'id',
                CmsBlockDefinition::class,
                false
            ))->addFlags(new ApiAware()),
        ]);
    }
}
