<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Monoid\Monoid;
use Generator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use RuntimeException;

/**
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
class LazyList implements IteratorAggregate
{
    /** @use Monad1<T> */
    use Monad1;
    /** @use Iterable1<T> */
    use Iterable1;

    /** @var iterable<int, T> */
    private $items;

    /** @param iterable<int, T> $iterable */
    final public function __construct(iterable $iterable)
    {
         $this->items = $iterable;
    }

    /**
     * @param int $low
     * @param int $high
     * @param int $step
     *
     * @return self<int>
     */
    public static function range(int $low, int $high = PHP_INT_MAX, int $step = 1): self
    {
         $range = static function (int $low, int $high, int $step = 1): Generator {
            while ($low <= $high) {
                yield $low;
                $low += $step;
            }
         };

        return new self($range($low, $high, $step));
    }

    /**
     * @param T $item
     * @param int|null $size - When no size is passed, infinite items are filled (lazily)
     *
     * @return static<T>
     */
    public static function fill($item, ?int $size = null)
    {
         $fill = static function ($item, ?int $size = null): Generator {
             $generated = 0;
            while ($size === null || $size > $generated) {
                yield $item;
                if ($size !== null) {
                    $generated++;
                }
            }
         };

        return new static($fill($item, $size));
    }

    /** @return static<T> */
    public static function fromEmpty()
    {
         /** @var array<mixed> $empty */
         $empty = [];

         return new static($empty);
    }

    /**
     * @param array<int, T> ...$items
     *
     * @return static<T>
     */
    public static function fromArray(array ...$items)
    {
         return static::fromEmpty()->concat(...$items);
    }

    /**
     * @template V
     *
     * @param iterable<V> $iterable
     *
     * @return static<V>
     */
    public static function fromIterable(iterable $iterable)
    {
         return new static($iterable);
    }

    /**
     * @template V
     *
     * @param V ...$items
     *
     * @return static<V>
     */
    public static function of(...$items)
    {
         return new static(array_values($items));
    }

    /**
     * @template V
     *
     * @param V $item
     *
     * @return static<V>
     */
    public static function pure($item)
    {
         return new static([$item]);
    }

    /**
     * @template B
     *
     * @param callable(T, int): B $mapper
     *
     * @return self<B>
     */
    public function map(callable $mapper): self
    {
         $map = function (callable $callback): Generator {
            foreach ($this->items as $key => $item) {
                yield $callback($item, $key);
            }
         };
        return new self($map($mapper));
    }

    /**
     * @template B
     *
     * @param callable(T, int): iterable<B> $mapper
     *
     * @return self<B>
     */
    public function flatMap(callable $mapper): self
    {
         return $this->map($mapper)->flatten();
    }

    /** @return self<mixed> */
    public function flatten(): self
    {
         $flatten = function (): Generator {
            foreach ($this->items as $item) {
                if (is_iterable($item)) {
                    yield from $item;
                } else {
                        throw new RuntimeException('Some item cannot be flattened because it is not iterable');
                }
            }
         };
        return new self($flatten());
    }

    /** @param callable(T, int): void $sideEffect */
    public function each(callable $sideEffect): void
    {
        foreach ($this->items as $key => $item) {
              $sideEffect($item, $key);
        }
    }

    /**
     * Defers execution of $sideEffect on each item of LazyList without materializing. Returns a new LazyList with same
     * contents (same unchanged items)
     *
     * Allows inserting side-effects in a chain of method calls.
     *
     * Also allows executing multiple side-effects on same LazyList
     *
     * Complexity: o(n)
     *
     * @param callable(T, int): void $sideEffect
     *
     * @return static<T>
     */
    public function tap(callable $sideEffect)
    {
        $tap = function () use ($sideEffect): Generator {
            foreach ($this->items as $key => $item) {
                $sideEffect($item, $key);
                yield $key => $item;
            }
        };

        return new self($tap());
    }

    /**
     * Computes reduction of the elements of the collection.
     *
     * @template R
     *
     * @param callable(R, T, int): R $reducer a binary operation for reduction
     * @param R $initialReduction
     *
     * @return R
     */
    public function reduce(callable $reducer, $initialReduction)
    {
        $reduction = $initialReduction;
        foreach ($this->items as $key => $item) {
              $reduction = $reducer($reduction, $item, $key);
        }
        return $reduction;
    }

    /**
     * Reduce (folds) List to single value using Monoid
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
        $reduction = $monoid->getEmpty();
        foreach ($this->items as $item) {
            $reduction = $monoid->concat($reduction, $item);
        }
        return $reduction;
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
        $reduction = 0;
        foreach ($this->items as $item) {
            $reduction += $itemToNumber($item);
        }
        return $reduction;
    }

    /**
     * Computes a prefix scan (reduction) of the elements of the collection.
     *
     * @template R
     *
     * @param callable(R, T, int): R $scanner a binary operation for scan (reduction)
     * @param R $initialReduction
     *
     * @return self<R> collection with intermediate scan (reduction) results
     */
    public function scan(callable $scanner, $initialReduction): self
    {
         $scan = function (callable $scanner, $initialReduction): Generator {
             $prefixReduction = $initialReduction;
            foreach ($this->items as $key => $item) {
                $prefixReduction = $scanner($prefixReduction, $item, $key);
                yield $prefixReduction;
            }
         };

        return new self($scan($scanner, $initialReduction));
    }

    /**
     * Materialize lazy list and executes items until it hits predicate
     *
     * Can be used in combination with tap to execute side effect
     * and early break after resolving last input passed to tap
     * against predicate.
     *
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return void
     */
    public function doWhile(callable $predicate): void
    {
        foreach ($this->items as $key => $item) {
            if (!$predicate($item, $key)) {
                break;
            }
        }
    }

    /**
     * @param callable(T, int): bool $predicate
     *
     * @return static<T>
     */
    public function takeWhile(callable $predicate)
    {
         $takeWhile = function (callable $whileCallback): Generator {
            foreach ($this->items as $key => $item) {
                if (!$whileCallback($item, $key)) {
                    break;
                }
                yield $item;
            }
         };
        return new static($takeWhile($predicate));
    }

    /**
     * @param int $size
     *
     * @return static<T>
     */
    public function take(int $size)
    {
         $take = function (int $size): Generator {
             $taken = 1;
            foreach ($this->items as $item) {
                yield $item;
                $taken++;
                if ($taken > $size) {
                    break;
                }
            }
         };
        return new static($take($size));
    }

    /** @return self<static<T>> */
    public function chunk(int $size): self
    {
         assert($size > 0, 'Size must be positive');
         $chunk = function (int $size): Generator {
             $materializedChunk = [];
            foreach ($this->items as $item) {
                $materializedChunk[] = $item;
                if (count($materializedChunk) === $size) {
                    yield static::fromArray($materializedChunk);
                    $materializedChunk = [];
                }
            }

            if (count($materializedChunk) !== 0) {
                yield static::fromArray($materializedChunk);
            }
         };

        return new self($chunk($size));
    }

    /** @return Option<T> */
    public function head(): Option
    {
         return $this->find(static function ($_): bool {
            return true;
         });
    }

    /** @return Option<T> */
    public function last(): Option
    {
         // No first item implies there is also no last item, thus we have to return none
         return $this->head()->isDefined()
         ? Option::some($this->reduce(static function ($_, $item) {
             return $item;
         }, null))
         : Option::none();
    }

    /**
     * @param callable(T, int): bool $predicate
     *
     * @return static<T>
     */
    public function filter(callable $predicate)
    {
         $filter = function (callable $predicate): Generator {
            foreach ($this->items as $key => $item) {
                if ($predicate($item, $key)) {
                    yield $item;
                }
            }
         };
        return new static($filter($predicate));
    }

    /**
     * @param callable(T, int): bool $predicate
     *
     * @return Option<T>
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
     * @param callable(T, int): bool $predicate
     *
     * @return static<T>
     */
    public function dropWhile(callable $predicate)
    {
         $drop = function (callable $dropCallback): Generator {
             $dropping = true;
            foreach ($this->items as $key => $item) {
                if ($dropping && $dropCallback($item, $key)) {
                    continue;
                }

                $dropping = false;

                yield $item;
            }
         };

        return new static($drop($predicate));
    }

    /**
     * @param int $count
     *
     * @return static<T>
     */
    public function drop(int $count)
    {
         $i = 0;
         return $this->dropWhile(static function ($_) use ($count, &$i): bool {
             return $i++ < $count;
         });
    }

    /**
     * @param callable(T, int): bool $predicate
     *
     * @return bool
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
     * @param callable(T, int): bool $predicate
     *
     * @return bool
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
     * @template B
     *
     * @param iterable<B> $iterable
     *
     * @return self<array{0: T, 1: B}>
     */
    public function zip(iterable $iterable): self
    {
        $rewind = static function (Iterator $iterator): void {
            $iterator->rewind();
        };
        $isValid = static function (Iterator $iterator): bool {
            return $iterator->valid();
        };
        $moveNext = static function (Iterator $iterator): void {
            $iterator->next();
        };
        $zip = function (iterable $iterable) use ($rewind, $isValid, $moveNext): Generator {
            $traversables = self::of($this->getIterator(), $this->createIterator($iterable));

            $traversables->each($rewind);
            while ($traversables->all($isValid)) {
                yield $traversables->map(static function (Iterator $iterator) {
                    return $iterator->current();
                })->toArray();
                $traversables->each($moveNext);
            }
        };
        return new self($zip($iterable));
    }

    /**
     * Maps with $mapper and zips with original values to keep track of which original value was mapped with $mapper
     *
     * This operation immediately materializes LazyList
     *
     * Complexity: o(n)
     *
     * @template B
     *
     * @param callable(T, int): B $mapper
     *
     * @return Map<T, B>
     */
    public function zipMap(callable $mapper): Map
    {
         return $this
         ->map(static function ($value, $key) use ($mapper): array {
             return [$value, $mapper($value, $key)];
         })
         ->toMap();
    }

    /**
     * @template T2
     *
     * @param iterable<T2> ...$iterables
     *
     * @return static<T|T2>
     */
    public function concat(iterable ...$iterables)
    {
         $append = function (array $iterables): Generator {
             yield from $this;
            foreach ($iterables as $iterator) {
                yield from $iterator;
            }
         };
        return new static($append($iterables));
    }

    /**
     * @param T ...$items
     *
     * @return static<T>
     */
    public function add(...$items)
    {
         return $this->concat(new self(array_values($items)));
    }

    /**
     * @param int $position
     * @param iterable<T> $iterable
     *
     * @return static<T>
     */
    public function insertOnPosition(int $position, iterable $iterable)
    {
         $insertOnPosition = function (int $position, iterable $iterable): Generator {
             $index = 0;
            foreach ($this->items as $item) {
                if ($index === $position) {
                    yield from $iterable;
                }
                $index++;
                yield $item;
            }
            if ($position >= $index) {
                throw new InvalidArgumentException(sprintf(
                    'Tried to insert collection to position %d, but only %d items were found',
                    $position,
                    $index
                ));
            }
         };
        return new static($insertOnPosition($position, $iterable));
    }

    /** @return array<int, T> */
    public function toArray(): array
    {
         return iterator_to_array($this->getIterator(), false);
    }

    public function join(string $glue): string
    {
         return implode($glue, $this->toArray());
    }

    /** @return Iterator<int, T> */
    public function getIterator(): Iterator
    {
         return $this->createIterator($this->items);
    }

    /** @return ArrayList<T> */
    public function toList(): ArrayList
    {
         return ArrayList::fromIterable($this);
    }

    /**
     * Creates a map from List of pairs.
     *
     * When called, you have to be sure, that list contains two element arrays, otherwise it will fails in runtime
     * with exception.
     *
     * Complexity: o(n)
     *
     * @return Map<mixed, mixed>
     */
    public function toMap(): Map
    {
        /** @var array<array{0: mixed, 1: mixed}> */
        $pairs = $this->toArray();
        return Map::fromIterable($pairs);
    }

    /**
     * @param iterable<T> $iterable
     *
     * @return Iterator<T>
     */
    private function createIterator(iterable $iterable): Iterator
    {
        if ($iterable instanceof Iterator) {
              return $iterable;
        }

        if ($iterable instanceof IteratorAggregate) {
              return $iterable->getIterator();
        }

        if (is_array($iterable)) {
              return new ArrayIterator($iterable);
        }

        // Fallback to generator, be aware, that it is not rewindable!
        return (static function (iterable $iterable): Generator {
            yield from $iterable;
        })($iterable);
    }
}
