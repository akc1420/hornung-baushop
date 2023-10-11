<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;

class Number extends AbstractFieldType
{
    public const NAME = 'number';

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

        $constraints['min'] = [new Type('numeric')];
        $constraints['max'] = [new Type('numeric')];
        $constraints['step'] = [new Type('numeric')];

        return $constraints;
    }

    /**
     * @param array<string, numeric> $config
     */
    public function validateConfig(array $config, string $path): ConstraintViolationList
    {
        $violations = parent::validateConfig($config, $path);

        if (!isset($config['min'], $config['max'])) {
            return $violations;
        }

        if ($config['min'] <= $config['max']) {
            return $violations;
        }

        $messageTemplate = 'Minimal value ({{ min }}) may not be larger than maximal value ({{ max }}) of number field.';

        $violations->add(new ConstraintViolation(
            \str_replace(\array_keys($config), $config, $messageTemplate),
            $messageTemplate,
            $config,
            null,
            \sprintf('%s/min', $path),
            null
        ));

        return $violations;
    }
}
