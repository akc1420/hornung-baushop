<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts;

/**
 * Interface RegistrationService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Authorization\Contracts
 */
interface RegistrationService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Returns shop owner registration data as base64 encoded json
     *
     * @return string base64 encoded json
     */
    public function getData();
}
