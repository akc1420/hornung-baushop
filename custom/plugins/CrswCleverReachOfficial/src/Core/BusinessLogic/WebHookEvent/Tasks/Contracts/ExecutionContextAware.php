<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Contracts;

/**
 * Interface ExecutionContextAware
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Tasks\Contracts
 */
interface ExecutionContextAware
{
    public function setExecutionContextProvider(callable $provider);
}