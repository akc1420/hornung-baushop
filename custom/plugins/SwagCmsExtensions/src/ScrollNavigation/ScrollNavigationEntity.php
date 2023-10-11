<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation;

use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationTranslation\ScrollNavigationTranslationCollection;

class ScrollNavigationEntity extends Entity
{
    use EntityIdTrait;

    protected bool $active;

    protected ?string $displayName;

    protected ?string $cmsSectionId;

    protected ?CmsSectionEntity $cmsSection;

    protected ?ScrollNavigationTranslationCollection $translations = null;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getCmsSectionId(): ?string
    {
        return $this->cmsSectionId;
    }

    public function setCmsSectionId(?string $cmsSectionId): void
    {
        $this->cmsSectionId = $cmsSectionId;
    }

    public function getCmsSection(): ?CmsSectionEntity
    {
        return $this->cmsSection;
    }

    public function setCmsSection(?CmsSectionEntity $cmsSection): void
    {
        $this->cmsSection = $cmsSection;
    }

    public function getTranslations(): ?ScrollNavigationTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(ScrollNavigationTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }
}
