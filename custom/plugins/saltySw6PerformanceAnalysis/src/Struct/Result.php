<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Struct;

use Shopware\Core\Framework\Struct\Struct;

class Result extends Struct
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $value;

    /** @var string */
    protected $suggestedValue;

    /** @var Status */
    protected $status;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    public function getSuggestedValue(): string
    {
        return $this->suggestedValue;
    }

    public function setSuggestedValue(string $suggestedValue): void
    {
        $this->suggestedValue = $suggestedValue;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): void
    {
        $this->status = $status;
    }
}
