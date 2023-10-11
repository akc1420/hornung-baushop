<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component;

use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\FormEntity;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotBlank;

abstract class AbstractComponentHandler
{
    abstract public function getComponentType(): string;

    public function prepareStorefront(FormEntity $form, FormGroupFieldEntity $field, SalesChannelContext $context): void
    {
    }

    public function render(FormGroupFieldEntity $field, DataBag $formData, SalesChannelContext $context): ?string
    {
        if (!$formData->has($field->getTechnicalName())) {
            return null;
        }

        return (string) $formData->get($field->getTechnicalName(), '');
    }

    /**
     * @return Constraint[]
     */
    public function getValidationDefinition(FormGroupFieldEntity $field, SalesChannelContext $context): array
    {
        if ($field->isRequired()) {
            return [new NotBlank()];
        }

        return [];
    }
}
