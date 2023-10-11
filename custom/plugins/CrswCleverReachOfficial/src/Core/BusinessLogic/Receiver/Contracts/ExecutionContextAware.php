<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts;

/**
 * Interface ExecutionContextAware
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Contracts
 */
interface ExecutionContextAware
{
    /**
     * Sets provider that resolves current execution context.
     *
     * @param callable $provider Provider that resolves ExecutionContext
     * @see \Crsw\CleverReachOfficial\Core\BusinessLogic\Receiver\Tasks\Composite\Context\ExecutionContext
     *      For the details of available context parameters.
     *
     * @return void
     */
    public function setExecutionContextProvider($provider);
}