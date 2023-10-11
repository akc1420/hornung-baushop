<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Swag\CmsExtensions\Form\FormEntity;

class FormTranslationEntity extends TranslationEntity
{
    protected string $swagCmsExtensionsFormId;

    protected ?FormEntity $swagCmsExtensionsForm = null;

    protected ?string $title = null;

    protected ?string $successMessage = null;

    /**
     * @var array<int|string, string>|null
     */
    protected ?array $receivers;

    public function getSwagCmsExtensionsFormId(): string
    {
        return $this->swagCmsExtensionsFormId;
    }

    public function setSwagCmsExtensionsFormId(string $swagCmsExtensionsFormId): void
    {
        $this->swagCmsExtensionsFormId = $swagCmsExtensionsFormId;
    }

    public function getSwagCmsExtensionsForm(): ?FormEntity
    {
        return $this->swagCmsExtensionsForm;
    }

    public function setSwagCmsExtensionsForm(?FormEntity $swagCmsExtensionsForm): void
    {
        $this->swagCmsExtensionsForm = $swagCmsExtensionsForm;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getSuccessMessage(): ?string
    {
        return $this->successMessage;
    }

    public function setSuccessMessage(?string $successMessage): void
    {
        $this->successMessage = $successMessage;
    }

    /**
     * @return array<int|string, string>|null
     */
    public function getReceivers(): ?array
    {
        return $this->receivers;
    }

    /**
     * @param array<int|string, string>|null $receivers
     */
    public function setReceivers(?array $receivers): void
    {
        $this->receivers = $receivers;
    }
}
