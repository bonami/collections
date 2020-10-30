<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Generator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use RuntimeException;
use Traversable;

/**
 * @template T
 * @implements IteratorAggregate<int, T>
 */
class LazyList implements IteratorAggregate
{
    use ApplicativeHelpers;

    /** @var iterable<T> */
    private $items;

    /**
     * @param iterable<T> $iterable
     */
    public function __construct(iterable $iterable)
    {
         $this->items = $iterable;
    }

    /**
     * @param int $low
     * @param int $high
     * @param int $step
     *
     * @return static<int>
     */
    public static function range(int $low, int $high = PHP_INT_MAX, int $step = 1): self
    {
         $range = static function (int $low, int $high, int $step = 1): Generator {
            while ($low <= $high) {
                yield $low;
                $low += $step;
            }
         };

        return new static($range($low, $high, $step));
    }

    /**
     * @param T $item
     * @param int|null $size
     *
     * @return self<T>
     */
    public static function fill($item, ?int $size = null): self
    {
         $fill = static function ($item, ?int $size = null): Generator {
             $generated = 0;
            while ($size === null ? true : $size > $generated) {
                yield $item;
                if ($size !== null) {
                    $generated++;
                }
            }
         };

        return new static($fill($item, $size));
    }

    /**
     * @return self<mixed>
     */
    public static function fromEmpty(): self
    {
         /** @var array<mixed> $empty */
         $empty = [];

         return new self($empty);
    }

    /**
     * @param array<T> ...$items
     *
     * @return self<T>
     */
    public static function fromArray(array ...$items): self
    {
         return self::fromEmpty()->concat(...$items);
    }

    /**
     * @param Traversable<int, T> $items
     *
     * @return self<T>
     */
    public static function fromTraversable(Traversable $items): self
    {
         return new self($items);
    }

    /**
     * @param iterable<T> $iterable
     *
     * @return self<T>
     */
    public static function fromIterable(iterable $iterable): self
    {
         return new self($iterable);
    }

    /**
     * @param T ...$items
     *
     * @return self<T>
     */
    public static function of(...$items): self
    {
         return new self($items);
    }

    /**
     * @param callable(mixed, int): mixed $mapper
     *
     * @return self<mixed>
     */
    public function map(callable $mapper)
    {
         $map = function (callable $callback): Generator {
            foreach ($this->items as $key => $item) {
                yield $callback($item, $key);
            }
         };
        return new static($map($mapper));
    }

    /**
     * @param self<mixed> $lazyList
     *
     * @return self<mixed>
     */
    public function ap(self $lazyList): self
    {
         $mappers = $this->map(function (callable $mapper) {
             return Lambda::of($mapper);
         })->toList();

         return $lazyList->flatMap(function ($value) use ($mappers) {
             return $mappers->map(function (Lambda $mapper) use ($value) {
                 return ($mapper)($value);
             });
         });
    }

    /**
     * @param callable(mixed, int): iterable<mixed> $mapper
     *
     * @return self<mixed>
     */
    public function flatMap(callable $mapper): self
    {
         return $this->map($mapper)->flatten();
    }

    /**
     * @return self<mixed>
     */
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

    /**
     * @param callable(mixed, int): void $sideEffect
     */
    public function each(callable $sideEffect): void
    {
        foreach ($this->items as $key => $item) {
              $sideEffect($item, $key);
        }
    }

    /**
     * Computes reduction of the elements of the collection.
     *
     * @template R
     * @param callable(R, mixed): R $reducer a binary operation for reduction
     * @param R $initialReduction
     *
     * @return R
     */
    public function reduce(callable $reducer, $initialReduction)
    {
         $reduction = $initialReduction;
        foreach ($this->items as $item) {
              $reduction = $reducer($reduction, $item);
        }
        return $reduction;
    }

    /**
     * Computes a prefix scan (reduction) of the elements of the collection.
     *
     * @template R
     * @param callable(R, mixed): R $scanner a binary operation for scan (reduction)
     * @param R $initialReduction
     *
     * @return self<R> collection with intermediate scan (reduction) results
     */
    public function scan(callable $scanner, $initialReduction): self
    {
         $scan = function (callable $scanner, $initialReduction): Generator {
             $prefixReduction = $initialReduction;
            foreach ($this->items as $item) {
                $prefixReduction = $scanner($prefixReduction, $item);
                yield $prefixReduction;
            }
         };

        return new self($scan($scanner, $initialReduction));
    }

    /**
     * @param callable(mixed): bool $predicate
     *
     * @return static<T>
     */
    public function takeWhile(callable $predicate)
    {
         $takeWhile = function (callable $whileCallback): Generator {
            foreach ($this->items as $item) {
                if (!$whileCallback($item)) {
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

    /**
     * @return self<static<T>>
     */
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

    /**
     * @return Option<T>
     */
    public function head(): Option
    {
         return $this->find(tautology());
    }

    /**
     * @return Option<T>
     */
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
     * @param callable(mixed, int): bool $predicate
     *
     * @return static<T>
     */
    public function filter(callable $predicate)
    {
         $filter = function (callable $filterCallback): Generator {
            foreach ($this->items as $key => $item) {
                if ($filterCallback($item, $key)) {
                    yield $item;
                }
            }
         };
        return new static($filter($predicate));
    }

    /**
     * @param callable(mixed): bool $predicate
     *
     * @return Option<T>
     */
    public function find(callable $predicate): Option
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return Option::some($item);
            }
        }

        return Option::none();
    }

    /**
     * @param callable(mixed): bool $predicate
     *
     * @return static<T>
     */
    public function dropWhile(callable $predicate)
    {
         $drop = function (callable $dropCallback): Generator {
             $dropping = true;
            foreach ($this->items as $item) {
                if ($dropping && $dropCallback($item)) {
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
         return $this->dropWhile(static function () use ($count, &$i) {
             return $i++ < $count;
         });
    }

    /**
     * @param callable(mixed): bool $predicate
     *
     * @return bool
     */
    public function exists(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param callable(mixed): bool $predicate
     *
     * @return bool
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->items as $item) {
            if (!$predicate($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @phpstan-template B
     * @phpstan-param iterable<B> $iterable
     *
     * @phpstan-return self<array{0: T, 1: B}>
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
            $traversables = self::of($this->getIterator(), $this->createTraversable($iterable));

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
     * @param callable $mapper - ($value: mixed, $index: int) => mixed
     *
     * @return Map<T, mixed>
     */
    public function zipMap(callable $mapper): Map
    {
         return $this
         ->map(function ($value, $key) use ($mapper): array {
             return [$value, $mapper($value, $key)];
         })
         ->toMap();
    }

    /**
     * @param iterable<T> ...$iterables
     *
     * @return static<T>
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
     * @return static<T>
     */
    public function add(...$items)
    {
         return $this->concat(new self($items));
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
                throw new InvalidArgumentException(
                    "Tried to insert collection to position {$position}, but only {$index} items were found"
                );
            }
         };
        return new static($insertOnPosition($position, $iterable));
    }

    /**
     * @return array<int, T>
     */
    public function toArray(): array
    {
         return iterator_to_array($this->getIterator(), false);
    }

    public function join(string $glue): string
    {
         return implode($glue, $this->toArray());
    }

    /**
     * @return Traversable<T>
     */
    public function getIterator(): Traversable
    {
         return $this->createTraversable($this->items);
    }

    /**
     * @return ArrayList<T>
     */
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
         return Map::fromIterable($this->toArray());
    }

    /**
     * @param iterable<T> $iterable
     * @return Traversable<T>
     */
    private function createTraversable(iterable $iterable): Traversable
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
        return (function (iterable $iterable): Generator {
            yield from $iterable;
        })($iterable);
    }
}
