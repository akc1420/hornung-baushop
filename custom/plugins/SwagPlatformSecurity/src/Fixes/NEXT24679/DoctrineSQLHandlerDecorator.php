<?php

declare(strict_types=1);

namespace Swag\Security\Fixes\NEXT24679;

use Shopware\Core\Framework\Log\Monolog\DoctrineSQLHandler;

class DoctrineSQLHandlerDecorator extends DoctrineSQLHandler
{
    protected function write(array $record): void
    {
        if (array_key_exists('channel', $record) && $record['channel'] === 'business_events') {
            return;
        }

        parent::write($record);
    }
}
