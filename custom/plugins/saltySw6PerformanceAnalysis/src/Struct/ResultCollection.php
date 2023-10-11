<?php

declare(strict_types=1);

namespace salty\Sw6PerformanceAnalysis\Struct;

use Shopware\Core\Framework\Struct\Collection;

class ResultCollection extends Collection
{
    protected function getExpectedClass(): ?string
    {
        return Result::class;
    }
}
