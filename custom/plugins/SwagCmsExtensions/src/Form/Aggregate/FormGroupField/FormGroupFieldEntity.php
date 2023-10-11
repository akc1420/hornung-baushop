<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupFieldTranslation\FormGroupFieldTranslationCollection;

class FormGroupFieldEntity extends Entity
{
    use EntityIdTrait;

    protected string $groupId;

    protected int $position;

    protected int $width;

    protected string $type;

    protected string $technicalName;

    /**
     * @var ?array<string, mixed>
     */
    protected ?array $config;

    protected bool $required;

    protected ?string $label = null;

    protected ?string $placeholder = null;

    protected ?string $errorMessage = null;

    protected ?FormGroupFieldTranslationCollection $translations = null;

    protected ?FormGroupEntity $group = null;

    public function getGroupId(): string
    {
        return $this->groupId;
    }

    public function setGroupId(string $groupId): void
    {
        $this->groupId = $groupId;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    /**
     * @return array<string, mixed>
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

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): void
    {
        $this->required = $required;
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

    public function getTranslations(): ?FormGroupFieldTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(FormGroupFieldTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getGroup(): ?FormGroupEntity
    {
        return $this->group;
    }

    public function setGroup(?FormGroupEntity $group): void
    {
        $this->group = $group;
    }
}
