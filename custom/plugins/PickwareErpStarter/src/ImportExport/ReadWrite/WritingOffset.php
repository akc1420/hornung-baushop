<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\ImportExport\ReadWrite;

use JsonSerializable;

class WritingOffset implements JsonSerializable
{
    private int $nextRowNumber;

    public function __construct(int $nextRowNumber)
    {
        $this->nextRowNumber = $nextRowNumber;
    }

    public function getNextRowNumber(): int
    {
        return $this->nextRowNumber;
    }

    public function setNextRowNumber(int $nextRowNumber): void
    {
        $this->nextRowNumber = $nextRowNumber;
    }

    public function jsonSerialize(): array
    {
        return ['nextRowNumber' => $this->nextRowNumber];
    }

    /**
     * @deprecated 3.0.0 'nextRowNumberToWriteToCsv' will not be accepted anymore. Use 'nextRowNumber' instead.
     */
    public static function fromArray(array $data): self
    {
        return new self($data['nextRowNumber'] ?? $data['nextRowNumberToWriteToCsv']);
    }
}
