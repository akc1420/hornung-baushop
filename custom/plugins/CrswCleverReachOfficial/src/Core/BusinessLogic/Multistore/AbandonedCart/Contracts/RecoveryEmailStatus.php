<?php

namespace Crsw\CleverReachOfficial\Core\BusinessLogic\Multistore\AbandonedCart\Contracts;

/**
 * Interface RecoveryEmailStatus\
 *
 * @package Crsw\CleverReachOfficial\Core\BusinessLogic\Automation\AbandonedCart\Contracts
 */
interface RecoveryEmailStatus
{
    const SENT = 'sent';
    const NOT_SENT = 'not_sent';
    const SENDING = 'sending';
    const PENDING = 'pending';
}
