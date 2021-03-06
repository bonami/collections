<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\NotImplementedException;
use Bonami\Collection\Exception\OutOfBoundsException;
use Bonami\Collection\Monoid\Monoid;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

use function array_chunk;
use function array_fill;
use function array_filter;
use function array_keys;
use function array_map;
use function array_reduce;
use function array_reverse;
use function array_slice;
use function array_values;
use function count;
use function explode;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function iterator_to_array;
use function method_exists;
use function spl_object_hash;
use function sprintf;
use function usort;

/**
 * @phpstan-template T
 *
 * @phpstan-implements IteratorAggregate<int, T>
 */
class ArrayList implements Countable, IteratorAggregate, JsonSerializable
{

    /** @phpstan-var array<int, T> */
    protected $items;

    /** @phpstan-param array<int, T> $items */
    final public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * Creates an empty List
     *
     * Complexity: o(1)
     *
     * @phpstan-return static<T>
     */
    public static function fromEmpty()
    {
        return new static([]);
    }

    /**
     * Creates a List with each enlisted item
     *
     * Complexity: o(n) - where n is number of passed items
     *
     * @phpstan-param T ...$item - with any number of occurences
     *
     * @phpstan-return static<T>
     */
    public static function of(...$item)
    {
        return new static($item);
    }

    /**
     * Fill a list with $value
     *
     * Complexity: o(n)
     *
     * @phpstan-param T $item - an item to be filled
     * @phpstan-param int $size - size of desired ArrayList with filled $item as each element
     *
     * @phpstan-return static<T>
     */
    public static function fill($item, int $size)
    {
        return new static(array_fill(0, $size, $item));
    }

    /**
     * Creates a List with range o values incremented by step
     *
     * Complexity: o(n) - where n is size of resulting List
     *
     * @see LazyList::range() - for initializing range lazily
     *
     * @phpstan-param int $min - a minimal (starting) value of range
     * @phpstan-param int $max - a maximum value of range - it may or maybe not be included
     *                 as last element if the step does not step
     *                 over it.
     * @phpstan-param int $step - a size of step between each item of range
     *
     * @phpstan-return self<int>
     */
    public static function range(int $min, int $max, int $step = 1): self
    {
        return LazyList::range($min, $max, $step)->toList();
    }

    /**
     * Creates a List of strings as a result of splitting the passed string by delimiter.
     *
     * It works with empty delimiter as well (as opposite to native php's explode function) which
     * causes creation of List where each element is single character string of original string.
     *
     * Complexity: o(n) - where n is length of string
     *
     * @phpstan-param string $delimiter - a delimiter to be used for spliting
     * @phpstan-param string $string - a string to be exploded
     *
     * @phpstan-return self<string>
     */
    public static function explode(string $delimiter, string $string): self
    {
        return $delimiter === ''
            ? new self(preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY) ?: [])
            : new self(explode($delimiter, $string) ?: []);
    }

    /**
     * Creates a List from any iterable collection.
     *
     * Internally, for optimization purposes, it simply boxes array and List
     * and iterates over the rest. If passed iterable is some kind of one-time
     * iterable collection (like Generator or LazyList) passing it into this
     * method will cause materialization (because it is immediately iterated over).
     *
     * Complexity: o(n) or o(1) - depending on type of iterable collection passed.
     *
     * @phpstan-param iterable<int, T> $iterable
     *
     * @phpstan-return static<T>
     */
    public static function fromIterable(iterable $iterable)
    {
        return new static(static::convertIterableToArray($iterable));
    }

    /**
     * Internal helper for converting iterable collection into array, which is internal
     * representation for items in ArrayList.
     *
     * Complexity: o(n) or o(1) - depending on type of iterable collection passed.
     *
     * @see fromIterable
     *
     * @internal
     *
     * @phpstan-param iterable<int, T> $iterable
     *
     * @phpstan-return array<T>
     */
    private static function convertIterableToArray(iterable $iterable): array
    {
        switch (true) {
            case is_array($iterable):
                return $iterable;
            case $iterable instanceof self:
                return $iterable->items;
            case $iterable instanceof Map:
                return $iterable->values()->items;
            case $iterable instanceof Traversable:
                return iterator_to_array($iterable, false);
            default:
                throw new NotImplementedException('Unimplemented iterable argument');
        }
    }

    /**
     * Returns rewindable iterator. Makes List `iterable`.
     * Allows use of ArrayList in foreach loops.
     *
     * Complexity: o(1) - Retriving the iterator itself is constant. Iterating over it is of course o(n)
     *
     * @phpstan-return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Simply returns size of List
     *
     * Complexity: o(1)
     *
     * @phpstan-return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Checks if the List is empty or not
     *
     * Complexity: o(1)
     *
     * @see isNotEmpty
     *
     * @phpstan-return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Checks if the List is empty or not
     *
     * Complexity: o(1)
     *
     * @see isEmpty
     *
     * @phpstan-return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->count() !== 0;
    }

    /**
     * Safely gets item in list by key wrapped in Option.
     *
     * Complexity: o(1)
     *
     * @see getUnsafe - when you are 100 % sure, that key is set
     * @see getOrElse - for unboxing value directly with alternative value when value is not set
     *
     * @phpstan-param int $key
     *
     * @phpstan-return Option<T>
     */
    public function get(int $key): Option
    {
        return array_key_exists($key, $this->items)
            ? Option::some($this->items[$key])
            : Option::none();
    }

    /**
     * Safely gets unwrapped item or alternative, when key is not defined
     *
     * Complexity: o(1)
     *
     * @see get - for getting Option instead of unboxing it directly
     * @see getUnsafe - when you are 100 % sure, that key is set
     *
     * @phpstan-template E
     *
     * @phpstan-param int $key
     * @phpstan-param E $else
     *
     * @phpstan-return T|E
     */
    public function getOrElse(int $key, $else)
    {
        return array_key_exists($key, $this->items)
            ? $this->items[$key]
            : $else;
    }

    /**
     * Gets unwrapped item or fail with exception, when key is not defined
     *
     * @see get - for getting Option instead of unboxing it directly
     * @see getOrElse - for unboxing value directly with alternative value when value is not set
     *
     * @phpstan-param int $key
     *
     * @throws OutOfBoundsException
     *
     * @phpstan-return T
     */
    public function getUnsafe(int $key)
    {
        if (array_key_exists($key, $this->items)) {
            return $this->items[$key];
        }

        throw new OutOfBoundsException("Key [$key] does not exist");
    }

    /**
     * Constructs a List containing all elements after applying callback
     * on each value.
     *
     * Complexity: o(n)
     *
     * @phpstan-template B
     *
     * @phpstan-param callable(T, int): B $mapper
     *
     * @phpstan-return self<mixed>
     */
    public function map(callable $mapper): self
    {
        return new self(array_map($mapper, $this->items, array_keys($this->items)));
    }

    /**
     * Can be called only on ArrayList containing closures. (It will fail in runtime if
     * this method is called on list which contains something different then callable)
     *
     * It will partially apply $values on each closure and returns partial functions. If
     * the closures are fully applied, then results are returned.
     *
     * Note, that it will create combinations for each partial apply (number of final results
     * are determined by number of closures and each $values list passed in partial apply. The
     * number is multiplication of respective sizes)
     *
     * Complexity: o(n)
     *
     * @phpstan-param self<mixed> $values
     *
     * @phpstan-return self<Lambda|mixed>
     */
    public function ap(self $values): self
    {
        $mappers = $this->map(static function (callable $mapper): Lambda {
            return Lambda::of($mapper);
        });

        return $values->flatMap(static function ($value) use ($mappers): self {
            /** @phpstan-var self<Lambda|mixed> $applied */
            $applied = $mappers->map(static function (Lambda $mapper) use ($value) {
                return ($mapper)($value);
            });
            return $applied;
        });
    }

    /**
     * Creates a new List as result of mapping and then flattening the result.
     *
     * In some other languages it is called bind operation. Classic monadic rules applies.
     *
     * Complexity: o(m) - where m is a number of resulting items in flatMapped List
     * This means it depends on size of original List and
     * on size of iterables returned from mapCallback
     *
     * @see map
     * @see flatten
     *
     * @phpstan-template B
     *
     * @phpstan-param callable(T, int): iterable<B> $mapper
     *
     * @phpstan-return self<B>
     */
    public function flatMap(callable $mapper): self
    {
        return self::fromIterable(LazyList::fromIterable($this->items)->flatMap($mapper));
    }

    /**
     * Flattens List of iterables into List - it reduces one level of dimensionality.
     *
     * Exception is thrown when List contains pure values instead of iterables.
     *
     * Complexity: o(m) - where m is a number of resulting items in flattened List
     * This means it depends on size of original List and
     * on size of iterables
     *
     * @see flatMap
     *
     * @phpstan-return self<mixed>
     */
    public function flatten(): self
    {
        return self::fromIterable(LazyList::fromIterable($this->items)->flatten());
    }

    /**
     * Creates a List as result of chained map and unique operation -
     * It first maps with $mapper and then it uniques the result.
     *
     * Unique operation, for optimization reasons, utilizes hashing
     * operation for objects. If mapped items implements IHashable,
     * IHashable::hashCode is used. If not, spl_object_id is used.
     *
     * This means, two objects with same data and different references
     * will not be treated as unique unless they implement own
     * IHashable::hashCode method.
     *
     * If two mapped items are not unique, the later is used in resulting List.
     *
     * Complexity: o(n) - where n is size of original List
     *
     * @see map
     * @see unique
     * @see uniqueBy
     * @see IHashable::hashCode
     *
     * @phpstan-template B
     *
     * @phpstan-param callable(T, int): B $mapper
     *
     * @phpstan-return self<B>
     */
    public function uniqueMap(callable $mapper): self
    {
        return $this
            ->map($mapper)
            ->index(static function ($a) {
                return $a;
            })
            ->values();
    }

    /**
     * Uniques List by $discriminator
     *
     * Unique operation, for optimization reasons, utilizes hashing
     * operation for objects. If items returned from $discriminator implements IHashable,
     * IHashable::hashCode is used. If not, spl_object_id is used.
     *
     * This means, two objects with same data and different references
     * will not be treated as unique unless they implement own
     * IHashable::hashCode method.
     *
     * If items are not unique by $discriminator, the later is used in resulting List.
     *
     * Complexity: o(n) - where n is size of original List
     *
     * @see map
     * @see unique
     * @see uniqueMap
     * @see IHashable::hashCode
     *
     * @phpstan-template B
     *
     * @phpstan-param callable(T, int): B $discriminator
     *
     * @phpstan-return static<T>
     */
    public function uniqueBy(callable $discriminator)
    {
        return static::fromIterable($this
            ->index(static function ($value, $key) use ($discriminator) {
                return $discriminator($value, $key);
            })
            ->values());
    }

    /**
     * Uniques List by self - semantically same as uniqueBy with identity() as discriminator
     *
     * Unique operation, for optimization reasons, utilizes hashing
     * operation for objects. If items returned from $discriminator implements IHashable,
     * IHashable::hashCode is used. If not, spl_object_id is used.
     *
     * This means, two objects with same data and different references
     * will not be treated as unique unless they implement own
     * IHashable::hashCode method.
     *
     * If items are not unique by $discriminator, the later is used in resulting List.
     *
     * Complexity: o(n) - where n is size of original List
     *
     * @see map
     * @see uniqueBy
     * @see uniqueMap
     * @see IHashable::hashCode
     *
     * @phpstan-return static<T>
     */
    public function unique()
    {
        return static::fromIterable($this->uniqueMap(static function ($a) {
            return $a;
        }));
    }

    /**
     * Appends items from iterable only if they are not already in the List.
     * As a side effect, it deduplicates all items, which was duplicit in
     * original List.
     *
     * It is a combination of chained concat and unique operations.
     *
     * Complexity: o(n + m) - where n is size of original List and m is a size of merged list.
     *
     * @see concat - to append items from iterable without deduplicating
     * @see unique - for more info about how deduplication work
     *
     * @phpstan-param iterable<T> $list
     *
     * @phpstan-return static<T>
     */
    public function union(iterable $list)
    {
        return $this->concat($list)->unique();
    }

    /**
     * Filters out List by given predicate
     *
     * Complexity: o(n)
     *
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return static<T>
     */
    public function filter(callable $predicate)
    {
        return new static(array_values(array_filter($this->items, $predicate, ARRAY_FILTER_USE_BOTH)));
    }

    /**
     * Finds first item in List by given predicate where it matches
     *
     * Complexity: o(n) - stops when predicate matches
     *
     * @see exists - if you just need to check if something matches by predicate
     * @see findKey - if you need to get key by predicate
     *
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return Option<T>
     */
    public function find(callable $predicate): Option
    {
        foreach ($this->items as $key => $item) {
            if ($predicate($item, $key)) {
                return Option::some($item);
            }
        }
        return Option::none();
    }

    /**
     * Finds first key in List by given predicate where it matches
     *
     * Complexity: o(n) - stops when predicate matches
     *
     * @see exists - if you just need to check if something matches by predicate
     * @see find - if you need to get item by predicate
     *
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return Option<T>
     */
    public function findKey(callable $predicate): Option
    {
        foreach ($this->items as $key => $item) {
            if ($predicate($item, $key)) {
                return Option::some($key);
            }
        }
        return Option::none();
    }

    /**
     * Checks if AT LEAST ONE item in List satisfies predicate.
     *
     * Complexity: o(n) - stops when predicate matches
     *
     * @see find - if you need to get item by predicate
     * @see contains - if you need to check item existence directly without predicate
     * @see all - if you need to check if ALL items in List satisfy predicate
     *
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return bool
     */
    public function exists(callable $predicate): bool
    {
        foreach ($this->items as $key => $item) {
            if ($predicate($item, $key)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if given item is present in List
     *
     * Complexity: o(n)
     *
     * @see exists - if you need to check if something exists by predicate
     * @see find - if you need to get item by predicate
     *
     * @phpstan-param T $item                     - item for lookup
     * @phpstan-param bool|null $strictComparison - if true, identity (===) comparison is used, equality otherwise (==)
     *
     * @phpstan-return bool
     */
    public function contains($item, ?bool $strictComparison = true): bool
    {
        return in_array($item, $this->items, $strictComparison ?? true);
    }

    /**
     * Checks if ALL items in List satisfy predicate
     *
     * Complexity: o(n) - stops immediately when some items does not satisfy predicate
     *
     * @see exists - if you need to check if AT LEAST ONE item in List satisfy predicate
     * @see find - if you need to get item by predicate
     *
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return bool
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->items as $key => $item) {
            if (!$predicate($item, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Sorts List by comparator
     *
     * Uses internal php sort algorithm. Sort stability depends on php version. Php authors
     * recommend to not depend on stability, because it can change over time.
     *
     * Complexity: o(n*log(n)) - uses internal php sort algorithm.
     *
     * @see comparator() - a default value for $comparator when ommited
     *
     * @phpstan-param null|callable(T, T): int $comparator - classic comparator returning 1, 0 or -1
     *                                                       if no comparator is passed, $first <=> $second is used
     *
     * @phpstan-return static<T>
     */
    public function sort(?callable $comparator = null)
    {
        $copied = $this->items;
        usort($copied, $comparator ?? comparator());
        return new static($copied);
    }

    /**
     * Creates a Map representing indexed values of original List, where key is created by $indexCallback
     *
     * Complexity: o(n)
     *
     * @phpstan-template M
     *
     * @phpstan-param callable(T, int): M $indexCallback
     *
     * @phpstan-return Map<M, T>
     */
    public function index(callable $indexCallback): Map
    {
        return Map::fromIterable($this->map(static function ($item, $key) use ($indexCallback) {
            return [$indexCallback($item, $key), $item];
        }));
    }

    /**
     * Gets classic php native array for interoperability
     *
     * Complexity: o(1)
     *
     * @phpstan-return array<T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Reduce List to single value by applying $reducer on each item with $carry from each step.
     *
     * Complexity: o(n)
     *
     * @phpstan-template R
     *
     * @phpstan-param callable(R, T, int): R $reducer - ($carry: mixed, $item: mixed, $key: int) => mixed
     * @phpstan-param R $initialReduction - initial value used as seed for $carry
     *
     * @phpstan-return R - reduced values. If the list is empty, $initialReduction is directly returned
     */
    public function reduce(callable $reducer, $initialReduction)
    {
        return array_reduce(array_keys($this->items), function ($carry, $key) use ($reducer) {
            return $reducer($carry, $this->items[$key], $key);
        }, $initialReduction);
    }

    /**
     * Reduce (folds) List to single value using Monoid
     *
     * Complexity: o(n)
     *
     * @see sum - for trivial summing
     *
     * @phpstan-param Monoid<T> $monoid
     *
     * @phpstan-return T
     */
    public function mfold(Monoid $monoid)
    {
        return array_reduce($this->items, static function ($carry, $next) use ($monoid) {
            return $monoid->concat($carry, $next);
        }, $monoid->getEmpty());
    }

    /**
     * Converts items to numbers and then sums them up.
     *
     * Complexity: o(n)
     *
     * @see mfold - for folding diferent types of items (E.g. classes representing BigNumbers and so on)
     *
     * @phpstan-param callable(T): (int|float) $itemToNumber
     *
     * @phpstan-return int|float
     */
    public function sum(callable $itemToNumber)
    {
        return array_reduce($this->items, static function ($carry, $next) use ($itemToNumber) {
            return $carry + $itemToNumber($next);
        }, 0);
    }

    /**
     * Finds minimal value defined by comparator
     *
     * Complexity: o(n)
     *
     * @phpstan-param null|callable(T, T): int $comparator - classic comparator returning 1, 0 or -1
     *                                                       if no comparator is passed, $first <=> $second is used
     *
     * @phpstan-return Option<T> minimal value wrapped in Option::some or Option::none when list is empty
     */
    public function min(?callable $comparator = null): Option
    {
        if ($this->isEmpty()) {
            return Option::none();
        }

        $comparator = $comparator ?? comparator();

        $min = $this->items[0];
        foreach ($this->items as $item) {
            if ($comparator($min, $item) > 0) {
                $min = $item;
            }
        }

        return Option::some($min);
    }

    /**
     * Finds maximal value defined by comparator
     *
     * Complexity: o(n)
     *
     * @phpstan-param null|callable(T, T): int $comparator - classic comparator returning 1, 0 or -1
     *                                                       if no comparator is passed, $first <=> $second is used
     *
     * @phpstan-return Option<T> minimal value wrapped in Option::some or Option::none when list is empty
     */
    public function max(?callable $comparator = null): Option
    {
        if ($this->isEmpty()) {
            return Option::none();
        }

        $comparator = $comparator ?? comparator();

        $max = $this->items[0];
        foreach ($this->items as $item) {
            if ($comparator($max, $item) < 0) {
                $max = $item;
            }
        }

        return Option::some($max);
    }

    /**
     * Executes $sideEffect on each item of List
     *
     * Complexity: o(n)
     *
     * @phpstan-param callable(T, int): void $sideEffect
     *
     * @phpstan-return void
     */
    public function each(callable $sideEffect): void
    {
        foreach ($this->items as $key => $item) {
            $sideEffect($item, $key);
        }
    }

    /**
     * Gets the very first value of List
     *
     * Complexity: o(1)
     *
     * @phpstan-return Option<T> item wrapped with Option::some or Option::none if list is empty
     */
    public function head(): Option
    {
        return array_key_exists(0, $this->items) ? Option::some($this->items[0]) : Option::none();
    }

    /**
     * Takes specified number of items from start
     *
     * Complexity: o(n) - where n is `$size`
     *
     * @phpstan-return static<T>
     */
    public function take(int $size)
    {
        return new static(array_slice($this->items, 0, $size, true));
    }

    /**
     * Gets sublist from original List starting from offset until end of in given limit if specified.
     *
     * Complexity: o(n) - where n is size of resulting List
     *
     * @phpstan-param int $offset     - from which index the slicing should start
     * @phpstan-param int|null $limit - how much items should be taken from offset.
     *                                  when nothing is specified, the items are taken until end of List
     *
     * @phpstan-return static<T>
     */
    public function slice(int $offset, ?int $limit = null)
    {
        return new static(array_slice($this->items, $offset, $limit));
    }

    /**
     * Gets the very last value of List
     *
     * Complexity: o(1)
     *
     * @phpstan-return Option<T> item wrapped with Option::some or Option::none if list is empty
     */
    public function last(): Option
    {
        $count = $this->count();
        if ($count === 0) {
            return Option::none();
        }

        return Option::some($this->items[$count - 1]);
    }

    /**
     * Creates a new list with null items filtered out
     *
     * Complexity: o(n)
     *
     * @phpstan-return static<T>
     */
    public function withoutNulls()
    {
        return $this->filter(static function ($item): bool {
            return $item !== null;
        });
    }

    /**
     * Return a new instance without given items.
     *
     * Complexity: o(n + m) or o(n * m)
     *
     * If strict comparison is used, the complexity is o(n + m).
     *
     * If non strict comparison is used, the complexity is o(n * m).
     *
     * n = number of items in this list
     * m = number of items to remove.
     *
     * @phpstan-param iterable<T> $itemsToRemove
     * @phpstan-param bool|null $strictComparison
     *
     * @phpstan-return static<T>
     */
    public function minus(iterable $itemsToRemove, ?bool $strictComparison = true)
    {
        if ($itemsToRemove instanceof self) {
            $itemsToRemoveArray = $itemsToRemove->items;
        } elseif ($itemsToRemove instanceof Traversable) {
            $itemsToRemoveArray = iterator_to_array($itemsToRemove);
        } else {
            $itemsToRemoveArray = $itemsToRemove;
        }

        if ($strictComparison) {
            /** @phpstan-var array<array{0: T, 1: bool}> */
            $pairs = array_map(static function ($item): array {
                return [$item, true];
            }, $itemsToRemoveArray);
            $itemsToRemoveIndex = Map::fromIterable($pairs);
            return $this->filter(
                static function ($item) use ($itemsToRemoveIndex): bool {
                    return !$itemsToRemoveIndex->has($item);
                }
            );
        }

        return $this->filter(
            static function ($item) use ($itemsToRemoveArray): bool {
                return !in_array($item, $itemsToRemoveArray, false);
            }
        );
    }

    /**
     * Return a new instance without given single item.
     *
     * Complexity: o(n) - strict comparison is slightly faster (with same complexity, but less hidden cost)
     *
     * @phpstan-param T $itemToRemove
     * @phpstan-param bool|null $strictComparison
     *
     * @phpstan-return static<T>
     */
    public function minusOne($itemToRemove, ?bool $strictComparison = true)
    {
        return $strictComparison
            ? $this->filter(static function ($item) use ($itemToRemove): bool {
                return $item !== $itemToRemove;
            })
            : $this->filter(static function ($item) use ($itemToRemove): bool {
                // phpcs:disable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
                return $item != $itemToRemove;
                // phpcs:enable SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
            });
    }

    /**
     * Concatenates this List with given items
     *
     * Complexity: o(n)
     *
     * @phpstan-template T2
     *
     * @phpstan-param iterable<T2> $itemsToAdd
     *
     * @phpstan-return static<T|T2>
     */
    public function concat(iterable $itemsToAdd)
    {
        return new static(array_merge($this->items, self::convertIterableToArray($itemsToAdd)));
    }

    /**
     * Finds common items from this List and given items.
     *
     * Complexity: o(n)
     *
     * @phpstan-param iterable<T> $items
     *
     * @phpstan-return static<T>
     */
    public function intersect(iterable $items)
    {
        $identity = static function ($a) {
            return $a;
        };
        return static::fromIterable($this->index($identity)->getByKeys($items)->keys());
    }

    /**
     * Groups items from List into Map, where keys are created from $groupBy callback
     *
     * Complexity: o(n)
     *
     * @phpstan-template G
     *
     * @phpstan-param callable(T, int): G $groupBy
     *
     * @phpstan-return Map<G, static<T>>
     */
    public function groupBy(callable $groupBy): Map
    {
        $grouped = new \Bonami\Collection\Mutable\Map([]);
        foreach ($this->items as $key => $item) {
            $grouped->getOrAdd($groupBy($item, $key), static::fromEmpty())->items[] = $item;
        }

        return Map::fromIterable($grouped);
    }

    /**
     * Creates List of Lists where each nested List has size of $size except last one,
     * which can have fewer items
     *
     * Complexity: o(n)
     *
     * @phpstan-param int $size - size of resulting nested List
     *
     * @phpstan-return self<static<T>>
     */
    public function chunk(int $size): self
    {
        return new ArrayList(
            array_map(
                static function ($chunk) {
                    return new static($chunk);
                },
                array_chunk($this->items, $size)
            )
        );
    }

    /**
     * Creates Map from List and given iterable, where this List will be used as keys
     * and given iterable as $values.
     *
     * Given values should be same size as List. If one of it is shorter, it will silently
     * ends combining with size of shorter collection.
     *
     * Complexity: o(n)
     *
     * @phpstan-template V
     *
     * @phpstan-param iterable<V> $values
     *
     * @phpstan-return Map<T, V>
     */
    public function combine(iterable $values): Map
    {
        return Map::fromIterable($this->zip($values));
    }

    /**
     * Create array tuples from List and given iterable
     *
     * It simply iterates through List and iterable alongside, picks one item at time and
     * combines them into array tuple.
     *
     * Zipping will end early when one of list or iterable is shorter.
     *
     * Complexity: o(n) where n is size of shortest collection
     *
     * @phpstan-template B
     *
     * @phpstan-param iterable<B> $iterable
     *
     * @phpstan-return self<array{0: T, 1: B}>
     */
    public function zip(iterable $iterable): self
    {
        return self::fromIterable(LazyList::fromIterable($this->items)->zip($iterable));
    }

    /**
     * Maps with $mapper and zips with original values to keep track of which original value was mapped with $mapper
     *
     * Complexity: o(n)
     *
     * @phpstan-template B
     *
     * @phpstan-param callable(T, int): B $mapper
     *
     * @phpstan-return Map<T, B>
     */
    public function zipMap(callable $mapper): Map
    {
        return $this->zip($this->map($mapper))->toMap();
    }

    /**
     * Creates string by imploding all items with glue. This works great when items are scalars
     * or has __toString method implemented.
     *
     * Complexity: o(n)
     *
     * @phpstan-param string $glue
     *
     * @phpstan-return string
     */
    public function join(string $glue): string
    {
        return implode($glue, $this->items);
    }

    /**
     * Converts List int php native array
     *
     * Complexity: o(n)
     *
     * @phpstan-return array<T>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Creates list with reversed item order
     *
     * Complexity: o(n)
     *
     * @phpstan-return static<T>
     */
    public function reverse()
    {
        return new static(array_reverse($this->items));
    }

    /**
     * Creates a map from List of pairs.
     *
     * When called, you have to be sure, that list contains two element arrays, otherwise it will fails in runtime
     * with exception.
     *
     * Complexity: o(n)
     *
     * @phpstan-return Map<mixed, mixed>
     */
    public function toMap(): Map
    {
        /** @phpstan-var array<array{0: mixed, 1: mixed}> */
        $pairs = $this->items;
        return Map::fromIterable($pairs);
    }

    /**
     * A helper method for converting item to human readable string.
     *
     * @internal
     *
     * @phpstan-param T $item
     *
     * @phpstan-return string
     */
    private function itemToString($item): string
    {
        switch (true) {
            case is_array($item):
                $stringifiedArray = implode(
                    ', ',
                    array_map(function ($i, $k) {
                        return "$k => {$this->itemToString($i)}";
                    }, $item, array_keys($item))
                );
                return '[' . $stringifiedArray . ']';
            case !is_object($item):
            case method_exists($item, '__toString'):
                return (string) $item;
            default:
                return sprintf('(%s) %s', get_class($item), spl_object_hash($item));
        }
    }

    /**
     * Classic toString method, mainly for debugging purposes.
     *
     * Complexity: o(n)
     */
    public function __toString(): string
    {
        return '[' . $this
                ->map(function ($item): string {
                    return $this->itemToString($item);
                })
                ->join(', ') . ']';
    }

    /**
     * Upgrades callable to accept and return `self` as arguments.
     *
     * @phpstan-param callable $callable
     *
     * @phpstan-return callable
     */
    final public static function lift(callable $callable): callable
    {
        return static function (self ...$arguments) use ($callable): self {
            $reducer = static function (self $applicative, self $argument): self {
                /** @phpstan-var mixed $argument */
                return $applicative->ap($argument);
            };
            return LazyList::fromIterable($arguments)
                ->reduce($reducer, self::of($callable));
        };
    }

    /**
     * Takes any `iterable<A>`, for each item `A` transforms to applicative with $mapperToApplicative
     * `A => self<B>` and cumulates it in `self<ArrayList<B>>`.
     *
     * @see sequence - behaves same as traverse, execept it is called with identity
     *
     * @phpstan-template A
     * @phpstan-template B
     *
     * @phpstan-param iterable<A> $iterable
     * @phpstan-param callable(A): self<B> $mapperToApplicative
     *
     * @phpstan-return self<ArrayList<B>>
     */
    final public static function traverse(iterable $iterable, callable $mapperToApplicative): self
    {
        $mapperToApplicative = $mapperToApplicative ?? static function ($a) {
            return $a;
        };
        return LazyList::fromIterable($iterable)
            ->reduce(
                static function (self $reducedApplicative, $impureItem) use ($mapperToApplicative): self {
                    $applicative = $mapperToApplicative($impureItem);
                    assert($applicative instanceof self);
                    return $reducedApplicative
                        ->map(static function (ArrayList $resultIterable): callable {
                            return static function ($item) use ($resultIterable): ArrayList {
                                return $resultIterable->concat(ArrayList::of($item));
                            };
                        })
                        ->ap($applicative);
                },
                self::of(ArrayList::fromEmpty())
            );
    }

    /**
     * Takes any `iterable<self<A>>` and sequence it into `self<ArrayList<A>>`. If any `self` is "empty", the result is
     * "short circuited".
     *
     * E. g. when called upon Option, when any instance is a None, then result is None.
     * If all instances are Some, the result is Some<ArrayList<A>>
     *
     * @phpstan-template A
     *
     * @phpstan-param iterable<self<A>> $iterable
     *
     * @phpstan-return self<ArrayList<A>>
     */
    final public static function sequence(iterable $iterable): self
    {
        /** @phpstan-var callable(self<A>): self<A> $identity */
        $identity = static function ($a) {
            return $a;
        };
        return self::traverse($iterable, $identity);
    }
}
