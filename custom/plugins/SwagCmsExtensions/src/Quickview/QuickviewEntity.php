<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Quickview;

use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class QuickviewEntity extends Entity
{
    use EntityIdTrait;

    protected bool $active;

    protected ?string $cmsBlockId = null;

    protected ?CmsBlockEntity $cmsBlock = null;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getCmsBlockId(): ?string
    {
        return $this->cmsBlockId;
    }

    public function setCmsBlockId(?string $cmsBlockId): void
    {
        $this->cmsBlockId = $cmsBlockId;
    }

    public function getCmsBlock(): ?CmsBlockEntity
    {
        return $this->cmsBlock;
    }

    public function setCmsBlock(?CmsBlockEntity $cmsBlock): void
    {
        $this->cmsBlock = $cmsBlock;
    }
}
