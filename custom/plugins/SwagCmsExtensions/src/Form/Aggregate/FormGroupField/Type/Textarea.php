<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\Type;

class Textarea extends AbstractFieldType
{
    public const NAME = 'textarea';

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array<string, array<Constraint>>
     */
    public function getConfigConstraints(): array
    {
        $constraints = parent::getConfigConstraints();

        $constraints['rows'] = [
            new Type('numeric'),
            new GreaterThan(0),
        ];
        $constraints['scalable'] = [new Type('bool')];

        return $constraints;
    }
}
