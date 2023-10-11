<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\DalBundle;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Throwable;

/**
 * Contains write commands only for a specific entity. In other ways similar to PreWriteValidationEvent. See the
 * `EntityPreWriteValidationEventDispatcher` for further information.
 */
class EntityPreWriteValidationEvent
{
    /**
     * @var WriteCommand[]
     */
    private array $commands;

    /**
     * @var class-string<EntityDefinition>
     */
    private string $definitionClassName;

    /**
     * @var Throwable[]
     */
    private array $violations = [];

    private WriteContext $writeContext;

    public function __construct(WriteContext $writeContext, array $commands, string $definitionClassName)
    {
        $this->writeContext = $writeContext;
        $this->commands = $commands;
        $this->definitionClassName = $definitionClassName;
    }

    public function getContext(): Context
    {
        return $this->writeContext->getContext();
    }

    public function getWriteContext(): WriteContext
    {
        return $this->writeContext;
    }

    /**
     * @return WriteCommand[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getDefinitionClassName(): string
    {
        return $this->definitionClassName;
    }

    public function getViolations(): array
    {
        return $this->violations;
    }

    public function addViolation(Throwable $violation): void
    {
        $this->violations[] = $violation;
    }
}
