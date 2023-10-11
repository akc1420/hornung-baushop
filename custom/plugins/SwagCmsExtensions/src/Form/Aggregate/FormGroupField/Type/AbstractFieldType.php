<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type;

use Shopware\Core\Framework\Struct\Struct;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolationList;

abstract class AbstractFieldType extends Struct
{
    abstract public function getName(): string;

    /**
     * @return array<string, array<Constraint>>
     */
    public function getConfigConstraints(): array
    {
        return [];
    }

    /**
     * @param array<string, mixed> $config
     */
    public function validateConfig(array $config, string $path): ConstraintViolationList
    {
        return new ConstraintViolationList();
    }
}
