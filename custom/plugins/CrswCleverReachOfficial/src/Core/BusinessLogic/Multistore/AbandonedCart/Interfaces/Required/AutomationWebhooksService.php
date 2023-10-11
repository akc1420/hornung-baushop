<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces\Required;

/**
 * Interface AutomationWebhooksService
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Interfaces
 */
interface AutomationWebhooksService
{
    const CLASS_NAME = __CLASS__;

    /**
     * Provides automation webhook url.
     *
     * @param $automationId
     *
     * @return string
     */
    public function getWebhookUrl($automationId);
}