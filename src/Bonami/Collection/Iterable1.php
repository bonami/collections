<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Monoid\Monoid;
use Iterator;

/** @template T */
trait Iterable1
{
    /**
     * Reduce to single value by applying $reducer on each item with $carry from each step.
     *
     * Complexity: o(n)
     *
     * @template R
     *
     * @param callable(R, T, int): R $reducer - ($carry: mixed, $item: mixed, $key: int) => mixed
     * @param R $initialReduction - initial value used as seed for $carry
     *
     * @return R - reduced values. If the list is empty, $initialReduction is directly returned
     */
    abstract public function reduce(callable $reducer, $initialReduction);

    /** @return Iterator <int, T> */
    abstract public function getIterator(): Iterator;

    /**
     * Reduce (folds) to single value using Monoid
     *
     * Complexity: o(n)
     *
     * @see sum - for trivial summing
     *
     * @param Monoid<T> $monoid
     *
     * @return T
     */
    public function mfold(Monoid $monoid)
    {
        return $this->reduce(static fn ($carry, $next) => $monoid->concat($carry, $next), $monoid->getEmpty());
    }

    /**
     * Converts items to numbers and then sums them up.
     *
     * Complexity: o(n)
     *
     * @see mfold - for folding diferent types of items (E.g. classes representing BigNumbers and so on)
     *
     * @param callable(T): (int|float) $itemToNumber
     *
     * @return int|float
     */
    public function sum(callable $itemToNumber)
    {
        return $this->reduce(static fn ($carry, $next) => $carry + $itemToNumber($next), 0);
    }

    /**
     * Finds first item by given predicate where it matches
     *
     * Complexity: o(n)
     *
     * @see exists - if you just need to check if something matches by predicate
     * @see findKey - if you need to get key by predicate
     *
     * @param callable(T, int): bool $predicate
     *
     * @return Option<T>
     */
    public function find(callable $predicate): Option
    {
        foreach ($this->getIterator() as $key => $item) {
            if ($predicate($item, $key)) {
                return Option::some($item);
            }
        }
        return Option::none();
    }

    /**
     * Gets the very first value
     *
     * Complexity: o(1)
     *
     * @return Option<T> item wrapped with Option::some or Option::none if empty
     */
    public function head(): Option
    {
        return $this->find(static fn () => true);
    }

    /**
     * Gets the very last value
     *
     * Complexity: o(n)
     *
     * @return Option<T> item wrapped with Option::some or Option::none if empty
     */
    public function last(): Option
    {
        return $this->reduce(static fn ($curry, $next) => Option::of($next), $this->head());
    }

    /**
     * Finds minimal value defined by comparator
     *
     * Complexity: o(n)
     *
     * @param null|callable(T, T): int $comparator - classic comparator returning 1, 0 or -1
     *                                                       if no comparator is passed, $first <=> $second is used
     *
     * @return Option<T> minimal value wrapped in Option::some or Option::none when list is empty
     */
    public function min(?callable $comparator = null): Option
    {
        $comparator ??= comparator();
        $min = Option::lift2(static fn ($a, $b) => $comparator($a, $b) <= 0 ? $a : $b);
        return $this->reduce(
            static fn ($next, $carry) => $min($next, Option::of($carry)),
            $this->head(),
        );
    }

    /**
     * Finds maximal value defined by comparator
     *
     * Complexity: o(n)
     *
     * @param null|callable(T, T): int $comparator - classic comparator returning 1, 0 or -1
     *                                                       if no comparator is passed, $first <=> $second is used
     *
     * @return Option<T> minimal value wrapped in Option::some or Option::none when list is empty
     */
    public function max(?callable $comparator = null): Option
    {
        $comparator ??= comparator();
        $max = Option::lift2(static fn ($a, $b) => $comparator($a, $b) > 0 ? $a : $b);
        return $this->reduce(
            static fn ($next, $carry) => $max($next, Option::of($carry)),
            $this->head(),
        );
    }

    /**
     * Checks if AT LEAST ONE item satisfies predicate.
     *
     * Complexity: o(n)
     *
     * @see find - if you need to get item by predicate
     * @see all - if you need to check if ALL items in List satisfy predicate
     *
     * @param callable(T, int): bool $predicate
     *
     * @return bool
     */
    public function exists(callable $predicate): bool
    {
        return $this->find($predicate)->isDefined();
    }

    /**
     * Checks if ALL items satisfy predicate
     *
     * Complexity: o(n)
     *
     * @see exists - if you need to check if AT LEAST ONE item in List satisfy predicate
     * @see find - if you need to get item by predicate
     *
     * @param callable(T, int): bool $predicate
     *
     * @return bool
     */
    public function all(callable $predicate): bool
    {
        return !$this->exists(static fn ($item, int $i) => !$predicate($item, $i));
    }

    /**
     * Executes $sideEffect on each item of List
     *
     * Complexity: o(n)
     *
     * @param callable(T, int): void $sideEffect
     *
     * @return void
     */
    public function each(callable $sideEffect): void
    {
        foreach ($this->getIterator() as $key => $item) {
            $sideEffect($item, $key);
        }
    }

    /**
     * Executes $sideEffect on each item and returns unchanged instance.
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(n)
     *
     * @param callable(T, int): void $sideEffect
     *
     * @return static<T>
     */
    public function tap(callable $sideEffect)
    {
        foreach ($this->getIterator() as $key => $item) {
            $sideEffect($item, $key);
        }

        return $this;
    }

    /**
     * Gets classic php native array for interoperability
     *
     * Complexity: o(1)
     *
     * @return array<T>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    /**
     * Gets classic php native array for interoperability
     *
     * Complexity: o(1)
     *
     * @return ArrayList<T>
     */
    public function toList(): ArrayList
    {
        return ArrayList::fromIterable($this->getIterator());
    }

    public function count(): int
    {
        return count($this->toArray());
    }

    /**
     * @see isNotEmpty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * @see isEmpty
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->count() !== 0;
    }
}
