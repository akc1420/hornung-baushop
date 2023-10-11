<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO;

/**
 * Class WebHook
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO
 */
class WebHook
{
    /**
     * @note Synonymous with group id.
     *
     * @var string
     */
    private $condition;
    /**
     * @var string
     */
    private $event;
    /**
     * @var array
     */
    private $payload;

    /**
     * WebHook constructor.
     *
     * @param string $condition
     * @param string $event
     * @param array $payload
     */
    public function __construct($condition, $event, array $payload)
    {
        $this->condition = $condition;
        $this->event = $event;
        $this->payload = $payload;
    }

    /**
     * @return string
     */
    public function getCondition()
    {
        return $this->condition;
    }

    /**
     * @return string
     */
    public function getEvent()
    {
        return $this->event;
    }

    /**
     * @return array
     */
    public function getPayload()
    {
        return $this->payload;
    }
}