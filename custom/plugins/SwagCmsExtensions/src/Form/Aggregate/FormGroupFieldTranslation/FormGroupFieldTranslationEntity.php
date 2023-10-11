<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;

class FormGroupFieldTranslationEntity extends TranslationEntity
{
    protected string $swagCmsExtensionsFormGroupFieldId;

    protected ?FormGroupFieldEntity $swagCmsExtensionsFormGroupField = null;

    protected ?string $label = null;

    protected ?string $placeholder = null;

    protected ?string $errorMessage = null;

    /**
     * @var ?array<string, mixed>
     */
    protected ?array $config;

    public function getSwagCmsExtensionsFormGroupFieldId(): string
    {
        return $this->swagCmsExtensionsFormGroupFieldId;
    }

    public function setSwagCmsExtensionsFormGroupFieldId(string $swagCmsExtensionsFormGroupFieldId): void
    {
        $this->swagCmsExtensionsFormGroupFieldId = $swagCmsExtensionsFormGroupFieldId;
    }

    public function getSwagCmsExtensionsFormGroupField(): ?FormGroupFieldEntity
    {
        return $this->swagCmsExtensionsFormGroupField;
    }

    public function setSwagCmsExtensionsFormGroupField(?FormGroupFieldEntity $swagCmsExtensionsFormGroupField): void
    {
        $this->swagCmsExtensionsFormGroupField = $swagCmsExtensionsFormGroupField;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): void
    {
        $this->label = $label;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function setPlaceholder(?string $placeholder): void
    {
        $this->placeholder = $placeholder;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return ?array<string, mixed>
     */
    public function getConfig(): ?array
    {
        return $this->config;
    }

    /**
     * @param ?array<string, mixed> $config
     */
    public function setConfig(?array $config): void
    {
        $this->config = $config;
    }
}
