<?php
/*
 * Copyright (c) Pickware GmbH. All rights reserved.
 * This file is part of software that is released under a proprietary license.
 * You must not copy, modify, distribute, make publicly available, or execute
 * its contents or parts thereof without express permission by the copyright
 * holder, unless otherwise permitted by law.
 */

declare(strict_types=1);

namespace Pickware\PickwareErpStarter\Collection;

use Countable;
use IteratorAggregate;
use Traversable;

/**
 * Keep this object immutable!
 *
 * @template Element
 */
class ImmutableCollection implements IteratorAggregate, Countable
{
    /**
     * @var Element[]
     */
    private array $elements = [];

    /**
     * @param Element[] $elements
     */
    final public function __construct(array $elements = [])
    {
        $this->elements = array_values($elements);
    }

    /**
     * @param callable(Element):bool $callback
     * @return static
     */
    final public function filter(callable $callback): self
    {
        return new static(array_values(array_filter($this->elements, $callback)));
    }

    /**
     * @template MappedElement
     * @param callable(Element):MappedElement $callback
     * @return ImmutableCollection<MappedElement>
     */
    final public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->elements));
    }

    /**
     * @template Carry
     * @param Carry $initialValue
     * @param callable(Carry, Element):Carry $callback
     * @return Carry
     */
    final public function reduce($initialValue, callable $callback)
    {
        return array_reduce($this->elements, $callback, $initialValue);
    }

    /**
     * @return static
     */
    final public function merge(self $other): self
    {
        return new static(array_merge($this->elements, $other->elements));
    }

    /**
     * @return Traversable<Element>
     */
    final public function getIterator(): Traversable
    {
        yield from $this->elements;
    }

    final public function count(): int
    {
        return count($this->elements);
    }

    final public function equals(self $other): bool
    {
        return $this->elements === $other->elements;
    }

    /**
     * @return Element[]
     */
    final public function asArray(): array
    {
        return $this->elements;
    }

    /**
     * @param Element $search
     */
    final public function containsElementEqualTo($search): bool
    {
        return $this->first(fn($element) => $element == $search) !== null;
    }

    /**
     * @param Element $search
     */
    final public function containsElementIdenticalTo($search): bool
    {
        return $this->first(fn($element) => $element === $search) !== null;
    }

    /**
     * @param callable(Element):bool $callback
     */
    final public function containsElementSatisfying(callable $callback): bool
    {
        return $this->first($callback) !== null;
    }

    /**
     * @param callable(Element):bool $callback
     * @return ?Element
     */
    final public function first(?callable $callback = null)
    {
        if (!$callback) {
            return $this->elements[0] ?? null;
        }

        foreach ($this->elements as $element) {
            if ($callback($element)) {
                return $element;
            }
        }

        return null;
    }

    /**
     * Returns a new instance with all duplicates removed.
     *
     * Works like array_unique.
     *
     * @see array_unique()
     *
     * @param int $flags See PHP method array_unique() for a documentation
     * @return static
     */
    final public function deduplicate(int $flags = SORT_STRING): self
    {
        return new static(array_values(array_unique($this->elements, $flags)));
    }
}
