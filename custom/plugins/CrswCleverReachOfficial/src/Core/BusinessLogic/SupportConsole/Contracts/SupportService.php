<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole\Contracts;

/**
 * Interface SupportService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\SupportConsole
 */
interface SupportService
{
    const CLASS_NAME = __CLASS__;
    /**
     * Return system configuration parameters
     *
     * @return array
     */
    public function get();

    /**
     * Updates system configuration parameters
     *
     * @param array $payload
     *
     * @return mixed
     */
    public function update(array $payload);
}
