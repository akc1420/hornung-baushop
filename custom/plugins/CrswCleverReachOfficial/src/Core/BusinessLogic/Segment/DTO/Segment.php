<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

/**
 * Class Segment
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO
 */
class Segment extends DataTransferObject
{
    /**
     * Segment's id from API.
     *
     * @var string
     */
    protected $id;
    /**
     * Segment name.
     *
     * @var string
     */
    protected $name;
    /**
     * List of rules.
     *
     * @var \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Rule[]
     */
    protected $rules;

    /**
     * Segment constructor.
     *
     * @param string $id
     * @param string $name
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Rule[] $rules
     */
    public function __construct($id, $name, array $rules)
    {
        $this->id = $id;
        $this->name = $name;
        $this->rules = $rules;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Rule[]
     */
    public function getRules()
    {
        return $this->rules;
    }

    /**
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Rule[] $rules
     */
    public function setRules($rules)
    {
        $this->rules = $rules;
    }

    /**
     * Creates segment instance from array of data.
     *
     * @param array $data
     *
     * @return \Crsw\CleverReachOfficial\Core\BusinessLogic\Segment\DTO\Segment
     */
    public static function fromArray(array $data)
    {
        return new self(
            static::getDataValue($data, 'id'),
            static::getDataValue($data, 'name'),
            Rule::fromBatch(static::getDataValue($data, 'rules', array()))
        );
    }

    /**
     * Retrieves array representation of the segment.
     *
     * @return array
     */
    public function toArray()
    {
        $rules = array();
        foreach ($this->rules as $rule) {
            $rules[] = $rule->toArray();
        }

        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'rules' => $rules,
        );
    }

    /**
     * Checks if given filter matches existing conditions
     *
     * @param string $filter
     *
     * @return bool
     */
    public function isConditionMatch($filter)
    {
        foreach ($this->getRules() as $rule) {
            if (stripos($rule->getCondition(), $filter) !== false) {
                return true;
            }
        }

        return false;
    }
}