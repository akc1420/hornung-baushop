<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Component\Handler;

use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\FormGroupFieldEntity;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type\Number;
use Swag\CmsExtensions\Form\Component\AbstractComponentHandler;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Type;

class NumberComponentHandler extends AbstractComponentHandler
{
    public function getComponentType(): string
    {
        return Number::NAME;
    }

    public function getValidationDefinition(FormGroupFieldEntity $field, SalesChannelContext $context): array
    {
        $parent = parent::getValidationDefinition($field, $context);
        $parent[] = new Type('numeric');

        $config = $field->getTranslation('config');

        if (isset($config['min'], $config['max'])) {
            $parent[] = new Range(['min' => $config['min'], 'max' => $config['max']]);

            return $parent;
        }

        if (isset($config['min'])) {
            $parent[] = new GreaterThanOrEqual($config['min']);

            return $parent;
        }

        if (isset($config['max'])) {
            $parent[] = new LessThanOrEqual($config['max']);

            return $parent;
        }

        return $parent;
    }
}
