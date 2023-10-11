<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroup;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldCollection;
use Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation\FormGroupTranslationCollection;
use Swag\CmsExtensions\Form\FormEntity;

class FormGroupEntity extends Entity
{
    use EntityIdTrait;

    protected string $formId;

    protected ?string $title = null;

    protected string $technicalName;

    protected int $position;

    protected ?FormGroupFieldCollection $fields = null;

    protected ?FormEntity $form = null;

    protected ?FormGroupTranslationCollection $translations = null;

    public function getFormId(): string
    {
        return $this->formId;
    }

    public function setFormId(string $formId): void
    {
        $this->formId = $formId;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function setTechnicalName(string $technicalName): void
    {
        $this->technicalName = $technicalName;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function getFields(): ?FormGroupFieldCollection
    {
        return $this->fields;
    }

    public function setFields(FormGroupFieldCollection $fields): void
    {
        $this->fields = $fields;
    }

    public function getForm(): ?FormEntity
    {
        return $this->form;
    }

    public function setForm(?FormEntity $form): void
    {
        $this->form = $form;
    }

    public function getTranslations(): ?FormGroupTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(FormGroupTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }
}
