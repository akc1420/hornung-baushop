<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component\Handler;

use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type\Checkbox;
use Swag\CmsExtensions\Form\Component\AbstractComponentHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

class CheckboxComponentHandler extends AbstractComponentHandler
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getComponentType(): string
    {
        return Checkbox::NAME;
    }

    public function render(FormGroupFieldEntity $field, DataBag $formData, SalesChannelContext $context): string
    {
        $data = $formData->get($field->getTechnicalName());

        if ($data === 'false') {
            $data = false;
        }

        return $this->translator->trans(\sprintf('swagCmsExtensions.form.component.checkbox.%s', $data ? 'true' : 'false'));
    }
}
