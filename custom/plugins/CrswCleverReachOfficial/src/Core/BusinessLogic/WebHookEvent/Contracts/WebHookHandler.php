<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts;

use Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook;

/**
 * Interface WebHookHandler
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Contracts
 */
interface WebHookHandler
{
    /**
     * Handles the web hook.
     *
     * @param \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\DTO\WebHook $hook
     *
     * @return void
     *
     * @throws \Crsw\CleverReachOfficial\Core\BusinessLogic\WebHookEvent\Exceptions\UnableToHandleWebHookException
     */
    public function handle(WebHook $hook);
}