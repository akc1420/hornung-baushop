<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO\Response\Filter;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Filter
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\DynamicContent\DTO
 */
class Filter extends DataTransferObject
{
    const INPUT = 'input';
    const DROPDOWN = 'dropdown';

    /**
     * @var string
     */
    protected $name;
    /**
     * @var string
     */
    protected $description;
    /**
     * @var bool
     */
    protected $required;
    /**
     * @var string
     */
    protected $queryKey;
    /**
     * @var string
     */
    protected $type;
    /**
     * @var DropdownOption[]
     */
    protected $dropdownOptions = array();

    /**
     * Filter constructor.
     *
     * @param string $name
     * @param string $queryKey
     * @param string $type
     */
    public function __construct($name, $queryKey, $type)
    {
        $allowedTypes = array(static::INPUT, static::DROPDOWN);
        if (!in_array($type, $allowedTypes, true)) {
            throw new \InvalidArgumentException("$type is not allowed. Allowed types: " . implode(', ', $allowedTypes));
        }

        $this->type = $type;
        $this->name = $name;
        $this->queryKey = $queryKey;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param bool $required
     */
    public function setRequired($required)
    {
        $this->required = $required;
    }


    /**
     * @param string $queryKey
     */
    public function setQueryKey($queryKey)
    {
        $this->queryKey = $queryKey;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param DropdownOption $dropdownOption
     */
    public function addDropdownOption(DropdownOption $dropdownOption)
    {
        $this->dropdownOptions[] = $dropdownOption;
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $data = array(
            'name' => $this->name,
            'description' => $this->description,
            'required' => (bool)$this->required,
            'query_key' => $this->queryKey,
            'type' => $this->type,
        );

        if ($this->type === self::DROPDOWN) {
            $data['values'] = $this->getDropdownOptions();
        }

        return $data;
    }

    /**
     * @return array
     */
    protected function getDropdownOptions()
    {
        $values = array();
        foreach ($this->dropdownOptions as $dropdownOption) {
            $values[] = $dropdownOption->toArray();
        }

        return $values;
    }
}