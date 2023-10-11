<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroup\FormGroupEntity;

class FormGroupTranslationEntity extends TranslationEntity
{
    protected string $swagCmsExtensionsFormGroupId;

    protected ?FormGroupEntity $swagCmsExtensionsFormGroup = null;

    protected ?string $title = null;

    public function getSwagCmsExtensionsFormGroupId(): string
    {
        return $this->swagCmsExtensionsFormGroupId;
    }

    public function setSwagCmsExtensionsFormGroupId(string $swagCmsExtensionsFormGroupId): void
    {
        $this->swagCmsExtensionsFormGroupId = $swagCmsExtensionsFormGroupId;
    }

    public function getSwagCmsExtensionsFormGroup(): ?FormGroupEntity
    {
        return $this->swagCmsExtensionsFormGroup;
    }

    public function setSwagCmsExtensionsFormGroup(?FormGroupEntity $swagCmsExtensionsFormGroup): void
    {
        $this->swagCmsExtensionsFormGroup = $swagCmsExtensionsFormGroup;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
