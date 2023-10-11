<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Swag\CmsExtensions\Form\Aggregate\FormGroupField;

use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Exception\FieldTypeAlreadyRegisteredException;
use Swag\CmsExtensions\Form\Aggregate\FormGroupField\Type\AbstractFieldType;
use Swag\CmsExtensions\Form\Component\ComponentRegistry;

class FormGroupFieldTypeRegistry
{
    /**
     * @var AbstractFieldType[]
     */
    private array $registeredTypes;

    private ComponentRegistry $componentRegistry;

    /**
     * @param iterable<AbstractFieldType> $fieldTypes
     */
    public function __construct(ComponentRegistry $componentRegistry, iterable $fieldTypes)
    {
        $this->componentRegistry = $componentRegistry;
        foreach ($fieldTypes as $fieldType) {
            $this->registerType($fieldType);
        }
    }

    public function getType(string $fieldType): ?AbstractFieldType
    {
        if (!isset($this->registeredTypes[$fieldType])) {
            return null;
        }

        return $this->registeredTypes[$fieldType];
    }

    /**
     * @throws FieldTypeAlreadyRegisteredException
     */
    private function registerType(AbstractFieldType $fieldType): void
    {
        $fieldTypeName = $fieldType->getName();
        if (isset($this->registeredTypes[$fieldTypeName])) {
            throw new FieldTypeAlreadyRegisteredException($fieldTypeName);
        }

        // component handler must be registered as well, throws exception otherwise
        $this->componentRegistry->getHandler($fieldTypeName);

        $this->registeredTypes[$fieldTypeName] = $fieldType;
    }
}
