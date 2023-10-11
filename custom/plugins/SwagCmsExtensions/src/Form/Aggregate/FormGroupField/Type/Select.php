<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type;

use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Salutation\SalutationDefinition;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Validation\ConfigValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\Unique;

class Select extends AbstractFieldType
{
    public const NAME = 'select';

    public const AVAILABLE_ENTITIES = [
        CountryDefinition::ENTITY_NAME,
        SalutationDefinition::ENTITY_NAME,
    ];

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

        $constraints['options'] = [
            new Count(['min' => 1]),
            new Unique(),
        ];
        $constraints['entity'] = [
            new Choice(self::AVAILABLE_ENTITIES),
        ];
        $constraints[ConfigValidator::CONFIG_ROOT_CONSTRAINTS] = [
            new Count(1),
        ];

        return $constraints;
    }
}
