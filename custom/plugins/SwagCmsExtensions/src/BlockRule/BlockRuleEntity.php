<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\BlockRule;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Rule\RuleEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class BlockRuleEntity extends Entity
{
    use EntityIdTrait;

    protected bool $inverted;

    protected ?string $visibilityRuleId = null;

    protected string $cmsBlockId;

    protected ?RuleEntity $visibilityRule = null;

    protected CmsBlockEntity $cmsBlock;

    public function getInverted(): bool
    {
        return $this->inverted;
    }

    public function setInverted(bool $inverted): void
    {
        $this->inverted = $inverted;
    }

    public function getVisibilityRuleId(): ?string
    {
        return $this->visibilityRuleId;
    }

    public function setVisibilityRuleId(?string $visibilityRuleId): void
    {
        $this->visibilityRuleId = $visibilityRuleId;
    }

    public function getCmsBlockId(): string
    {
        return $this->cmsBlockId;
    }

    public function setCmsBlockId(string $cmsBlockId): void
    {
        $this->cmsBlockId = $cmsBlockId;
    }

    public function getVisibilityRule(): ?RuleEntity
    {
        return $this->visibilityRule;
    }

    public function setVisibilityRule(?RuleEntity $visibilityRule): void
    {
        $this->visibilityRule = $visibilityRule;
    }

    public function getCmsBlock(): CmsBlockEntity
    {
        return $this->cmsBlock;
    }

    public function setCmsBlock(CmsBlockEntity $cmsBlock): void
    {
        $this->cmsBlock = $cmsBlock;
    }
}
