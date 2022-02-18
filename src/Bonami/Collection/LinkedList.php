<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;

/**
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
class LinkedList implements Countable, IteratorAggregate, JsonSerializable
{
    /** @use Monad1<T> */
    use Monad1;
    /** @use Iterable1<T> */
    use Iterable1;

    /** @var ?T */
    private $head;

    /** @var ?self<T> */
    private $tail;

    /**
     * @param ?T $head
     * @param ?self<T> $tail
     */
    final private function __construct($head, $tail)
    {
        $this->head = $head;
        $this->tail = $tail;
    }

    /**
     * Wraps impure item into pure context of type class.
     *
     * @template A
     *
     * @param A $value
     *
     * @return static<A>
     */
    public static function pure($value)
    {
        return new static($value, null);
    }

    /**
     * @template A
     *
     * @param iterable<A> $xs
     *
     * @return static<A>
     */
    public static function fromIterable(iterable $xs)
    {
        $reversed = ArrayList::fromIterable($xs)->reverse();
        $list = self::fromEmpty();
        foreach ($reversed as $x) {
            $list = $list->prepend($x);
        }

        return $list;
    }

    /** @return static<T> */
    public static function fromEmpty()
    {
        return new static(null, null);
    }

    /**
     * Maps over values wrapped in context of type class.
     *
     * @template A
     *
     * @param callable(T, int=): A $mapper
     *
     * @return self<A>
     */
    public function map(callable $mapper): self
    {
        /** @var ?A $nullA */
        $nullA = null;
        return $this->head === null
            ? new self($nullA, $nullA)
            : new self($mapper($this->head), $this->tail()->map($mapper));
    }

    /**
     * Reduce to single value by applying $reducer on each item with $carry from each step.
     *
     * Complexity: o(n)
     *
     * @template R
     *
     * @param callable(R, T, int=): R $reducer - ($carry: mixed, $item: mixed, $key: int) => mixed
     * @param R $initialReduction - initial value used as seed for $carry
     *
     * @return R - reduced values. If the list is empty, $initialReduction is directly returned
     */
    public function reduce(callable $reducer, $initialReduction)
    {
        if ($this->head === null) {
            return $initialReduction;
        }

        return $this->tail()->reduce($reducer, $reducer($initialReduction, $this->head));
    }

    /** @return Iterator<T> */
    public function getIterator(): Iterator
    {
        return new class ($this) implements Iterator {
            /** @var LinkedList<T> */
            private LinkedList $list;

            /** @var LinkedList<T> */
            private LinkedList $currentList;
            private int $key;

            /** @param LinkedList<T> $list */
            public function __construct(LinkedList $list)
            {
                $this->list = $list;
                $this->currentList = $list;
                $this->key = 0;
            }

            /** @return T */
            public function current()
            {
                return $this->currentList->head()->getUnsafe();
            }

            public function next(): void
            {
                $this->currentList = $this->currentList->tail();
                $this->key++;
            }

            public function key(): int
            {
                return $this->key;
            }

            public function valid(): bool
            {
                return !$this->currentList->isEmpty();
            }

            public function rewind(): void
            {
                $this->currentList = $this->list;
                $this->key = 0;
            }
        };
    }

    /**
     * Chain mapper call on LinkedList
     *
     * @template B
     *
     * @param callable(T, int): iterable<B> $mapper
     *
     * @return self<B>
     */
    public function flatMap(callable $mapper): self
    {
        return $this
            ->map($mapper)
            ->reduce(static fn ($flattened, $curr) => $flattened->concat($curr), new self(null, null));
    }

    /** @return array<T> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return static<T> */
    public function tail()
    {
        return $this->tail ?? self::fromEmpty();
    }

    /** @return Option<T> */
    public function head()
    {
        return Option::fromNullable($this->head);
    }

    /** @return Option<T> */
    public function last()
    {
        return $this->reduce(static fn ($x) => $x, $this->head());
    }

    public function isEmpty(): bool
    {
        return $this->head === null;
    }

    /**
     * @template T2
     *
     * @param T2 $head
     *
     * @return static<T|T2>
     */
    public function prepend($head)
    {
        return new static($head, $this);
    }

    /**
     * @template T2
     *
     * @param iterable<T2> $xs
     *
     * @return static<T|T2>
     */
    public function concat(iterable $xs)
    {
        // @phpstan-ignore-next-line
        return $this->isEmpty()
            ? static::fromIterable($xs)
            : static::fromIterable(LazyList::fromIterable($this)->concat($xs));
    }
}
