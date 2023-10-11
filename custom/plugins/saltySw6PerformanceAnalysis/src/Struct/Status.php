<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Status extends Struct
{
    public const STATUS_PASSED              = 'success';
    public const STATUS_PASSED_WITH_WARNING = 'warning';
    public const STATUS_FAILED              = 'error';
    public const STATUS_INFO                = 'info';

    /** @var string */
    protected $type;

    /** @var string */
    protected $notification;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getNotification(): string
    {
        return $this->notification;
    }

    public function setNotification(string $notification): void
    {
        $this->notification = $notification;
    }
}
