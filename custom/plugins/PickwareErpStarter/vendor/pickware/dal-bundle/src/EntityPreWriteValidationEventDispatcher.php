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

use Psr\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Validation\PreWriteValidationEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Shopware dispatches only one "pre_write_validation" event for each call to the DAL (like entity create, update or
 * delete). The same event is dispatched for every entity that is written at once, so subscribers listening on this
 * event then need to be instantiated in the container even if they do not need information about the entity in
 * question. Only after their instantiation they can decide whether to act or not.
 *
 * This dispatcher and the new event being only scoped on individual entities solves this problem by allowing subscribers
 * to specify the concerned entities before their instantiation. This means that the symfony DI container will not
 * instantiate a subscriber if the subscriber will not act on the event.
 *
 * During plugin updates, the "plugin" entity is updated and thus a "pre_write_validation" event is dispatched. When
 * listening on the old event, subscribers were instantiated even if they did not act on the "plugin" entity. As during
 * a plugin update the code is updated before the container refreshes, the container might try to instantiate a
 * subscriber with invalid constructor parameters. This leads to a (from the admin irrecoverable) container crash. With
 * the new event, the subscriber is not instantiated and thus the container does not crash.
 *
 * This also prevents subscribers from requesting change-sets for every entity and uncovers missing change-set requests.
 *
 * See:
 * - https://github.com/pickware/shopware-plugins/issues/3500
 * - https://github.com/pickware/shopware-plugins/issues/2764
 */
class EntityPreWriteValidationEventDispatcher implements EventSubscriberInterface
{
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents(): array
    {
        return [PreWriteValidationEvent::class => 'onPreWriteValidation'];
    }

    public static function getEventName(string $entityName): string
    {
        return sprintf('pickware_dal_bundle.%s.pre_write_validation', $entityName);
    }

    public function onPreWriteValidation(PreWriteValidationEvent $event): void
    {
        $writeCommandsByEntityNames = [];
        $definitionClassNamesByEntityNames = [];
        foreach ($event->getCommands() as $command) {
            $entityName = $command->getDefinition()->getEntityName();
            $writeCommandsByEntityNames[$entityName] ??= [];
            $writeCommandsByEntityNames[$entityName][] = $command;
            $definitionClassNamesByEntityNames[$entityName] = $command->getDefinition()->getClass();
        }

        foreach ($writeCommandsByEntityNames as $entityName => $writeCommands) {
            /** @var EntityPreWriteValidationEvent $entityPreWriteValidationEvent */
            $entityPreWriteValidationEvent = $this->eventDispatcher->dispatch(
                new EntityPreWriteValidationEvent(
                    $event->getWriteContext(),
                    $writeCommands,
                    $definitionClassNamesByEntityNames[$entityName],
                ),
                self::getEventName($entityName),
            );

            foreach ($entityPreWriteValidationEvent->getViolations() as $violation) {
                $event->getExceptions()->add($violation);
            }
        }
    }
}
