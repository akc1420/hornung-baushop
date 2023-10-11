<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\ScrollNavigation\Aggregate\ScrollNavigationTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\System\Language\LanguageEntity;
use Swag\CmsExtensions\ScrollNavigation\ScrollNavigationEntity;

class ScrollNavigationTranslationEntity extends Entity
{
    protected string $swagCmsExtensionsScrollNavigationId;

    protected ?string $displayName;

    protected ?ScrollNavigationEntity $swagCmsExtensionsScrollNavigation;

    protected string $languageId;

    protected ?LanguageEntity $language;

    public function getSwagCmsExtensionsScrollNavigationId(): string
    {
        return $this->swagCmsExtensionsScrollNavigationId;
    }

    public function setSwagCmsExtensionsScrollNavigationId(string $swagCmsExtensionsScrollNavigationId): void
    {
        $this->swagCmsExtensionsScrollNavigationId = $swagCmsExtensionsScrollNavigationId;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(?string $displayName): void
    {
        $this->displayName = $displayName;
    }

    public function getSwagCmsExtensionsScrollNavigation(): ?ScrollNavigationEntity
    {
        return $this->swagCmsExtensionsScrollNavigation;
    }

    public function setSwagCmsExtensionsScrollNavigation(?ScrollNavigationEntity $swagCmsExtensionsScrollNavigation): void
    {
        $this->swagCmsExtensionsScrollNavigation = $swagCmsExtensionsScrollNavigation;
    }

    public function getLanguageId(): string
    {
        return $this->languageId;
    }

    public function setLanguageId(string $languageId): void
    {
        $this->languageId = $languageId;
    }

    public function getLanguage(): ?LanguageEntity
    {
        return $this->language;
    }

    public function setLanguage(?LanguageEntity $language): void
    {
        $this->language = $language;
    }
}
