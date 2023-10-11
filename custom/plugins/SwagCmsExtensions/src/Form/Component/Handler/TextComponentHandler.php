<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component\Handler;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type\Text;
use Swag\CmsExtensions\Form\Component\AbstractComponentHandler;
use Symfony\Component\Validator\Constraints\Type;

class TextComponentHandler extends AbstractComponentHandler
{
    public function getComponentType(): string
    {
        return Text::NAME;
    }

    public function getValidationDefinition(FormGroupFieldEntity $field, SalesChannelContext $context): array
    {
        $parent = parent::getValidationDefinition($field, $context);
        $parent[] = new Type('string');

        return $parent;
    }
}
