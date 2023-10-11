<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Mailing\DTO;

use Crsw\CleverReachOfficial\Core\Infrastructure\Data\DataTransferObject;

class MailingReceivers extends DataTransferObject
{
    /**
     * @var array
     */
    protected $groups = array();
    /**
     * @var string
     */
    protected $filter = '';

    /**
     * Retrieves groups.
     *
     * @return array
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * Sets groups.
     *
     * @param array $groups Groups.
     */
    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    /**
     * Retrieves filter.
     *
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Sets filter.
     *
     * @param string $filter Filter.
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
    }

    /**
     * Transforms an instance to an array.
     *
     * @return array Array representation.
     */
    public function toArray()
    {
        $result = array();

        if (!empty($this->groups)) {
            $result['groups'] = $this->groups;
        }

        if (!empty($this->filter)) {
            $result['filter'] = $this->filter;
        }

        return $result;
    }

    /**
     * Creates an instance from the array.
     *
     * @param array $data Array of raw data.
     *
     * @return static Static instance.
     */
    public static function fromArray(array $data)
    {
        $entity = new static;

        $entity->setGroups(static::getDataValue($data, 'groups', array()));
        $entity->setFilter(static::getDataValue($data, 'filter'));

        return $entity;
    }
}