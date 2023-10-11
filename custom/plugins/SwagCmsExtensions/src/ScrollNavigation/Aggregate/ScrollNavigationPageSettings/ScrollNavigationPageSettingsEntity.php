<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationPageSettings;

use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ScrollNavigationPageSettingsEntity extends Entity
{
    use EntityIdTrait;

    protected bool $active;

    protected int $duration;

    protected string $easing;

    protected bool $bouncy;

    protected int $easingDegree;

    protected ?string $cmsPageId;

    protected ?CmsPageEntity $cmsPage;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): void
    {
        $this->duration = $duration;
    }

    public function getEasing(): string
    {
        return $this->easing;
    }

    public function setEasing(string $easing): void
    {
        $this->easing = $easing;
    }

    public function isBouncy(): bool
    {
        return $this->bouncy;
    }

    public function setBouncy(bool $bouncy): void
    {
        $this->bouncy = $bouncy;
    }

    public function getEasingDegree(): int
    {
        return $this->easingDegree;
    }

    public function setEasingDegree(int $easingDegree): void
    {
        $this->easingDegree = $easingDegree;
    }

    public function getCmsPageId(): ?string
    {
        return $this->cmsPageId;
    }

    public function setCmsSectionId(?string $cmsPageId): void
    {
        $this->cmsPageId = $cmsPageId;
    }

    public function getCmsPage(): ?CmsPageEntity
    {
        return $this->cmsPage;
    }

    public function setCmsPage(?CmsPageEntity $cmsPage): void
    {
        $this->cmsPage = $cmsPage;
    }
}
