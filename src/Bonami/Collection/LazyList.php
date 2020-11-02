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
 * @phpstan-template T
 * @phpstan-implements IteratorAggregate<int, T>
 */
class LazyList implements IteratorAggregate
{
    /** @phpstan-var iterable<int, T> */
    private $items;

    /**
     * @phpstan-param iterable<int, T> $iterable
     */
    public function __construct(iterable $iterable)
    {
         $this->items = $iterable;
    }

    /**
     * @phpstan-param int $low
     * @phpstan-param int $high
     * @phpstan-param int $step
     *
     * @phpstan-return static<int>
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
     * @phpstan-param T $item
     * @phpstan-param int|null $size - When no size is passed, infinite items are filled (lazily)
     *
     * @phpstan-return self<T>
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
     * @phpstan-return self<T>
     */
    public static function fromEmpty(): self
    {
         /** @phpstan-var array<mixed> $empty */
         $empty = [];

         return new self($empty);
    }

    /**
     * @phpstan-param array<int, T> ...$items
     *
     * @phpstan-return self<T>
     */
    public static function fromArray(array ...$items): self
    {
         return self::fromEmpty()->concat(...$items);
    }

    /**
     * @phpstan-param Traversable<T> $items
     *
     * @phpstan-return self<T>
     */
    public static function fromTraversable(Traversable $items): self
    {
         return new self($items);
    }

    /**
     * @phpstan-param iterable<T> $iterable
     *
     * @phpstan-return self<T>
     */
    public static function fromIterable(iterable $iterable): self
    {
         return new self($iterable);
    }

    /**
     * @phpstan-param T ...$items
     *
     * @phpstan-return self<T>
     */
    public static function of(...$items): self
    {
         return new self($items);
    }

    /**
     * @phpstan-template B
     * @phpstan-param callable(T, int): B $mapper
     *
     * @phpstan-return self<mixed>
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
     * @phpstan-param self<mixed> $lazyList
     *
     * @phpstan-return self<Lambda|mixed>
     */
    public function ap(self $lazyList): self
    {
         $mappers = $this->map(static function (callable $mapper) {
             return Lambda::of($mapper);
         })->toList();

         return $lazyList->flatMap(static function ($value) use ($mappers): iterable {
             /** @phpstan-var self<Lambda|mixed> $applied */
             $applied = $mappers->map(static function (Lambda $mapper) use ($value) {
                 return ($mapper)($value);
             });
             return $applied;
         });
    }

    /**
     * @phpstan-template B
     * @phpstan-param callable(T, int): iterable<B> $mapper
     *
     * @phpstan-return self<T>
     */
    public function flatMap(callable $mapper): self
    {
         return $this->map($mapper)->flatten();
    }

    /**
     * @phpstan-return self<mixed>
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
     * @phpstan-param callable(T, int): void $sideEffect
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
     * @phpstan-template R
     * @phpstan-param callable(R, T, int): R $reducer a binary operation for reduction
     * @phpstan-param R $initialReduction
     *
     * @phpstan-return R
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
     * Computes a prefix scan (reduction) of the elements of the collection.
     *
     * @phpstan-template R
     * @phpstan-param callable(R, T, int): R $scanner a binary operation for scan (reduction)
     * @phpstan-param R $initialReduction
     *
     * @phpstan-return self<R> collection with intermediate scan (reduction) results
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
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return static<T>
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
     * @phpstan-param int $size
     *
     * @phpstan-return static<T>
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
     * @phpstan-return self<static<T>>
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
     * @phpstan-return Option<T>
     */
    public function head(): Option
    {
         return $this->find(static function ($_): bool {
            return true;
         });
    }

    /**
     * @phpstan-return Option<T>
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
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return static<T>
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
     * @phpstan-param callable(T, int): bool $predicate
     *
     * @phpstan-return static<T>
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
     * @phpstan-param int $count
     *
     * @phpstan-return static<T>
     */
    public function drop(int $count)
    {
         $i = 0;
         return $this->dropWhile(static function ($_) use ($count, &$i): bool {
             return $i++ < $count;
         });
    }

    /**
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
     * @phpstan-template B
     * @phpstan-param callable(T, int): B $mapper
     *
     * @phpstan-return Map<T, B>
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
     * @phpstan-param iterable<T> ...$iterables
     *
     * @phpstan-return static<T>
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
     * @phpstan-param T ...$items
     * @phpstan-return static<T>
     */
    public function add(...$items)
    {
         return $this->concat(new self($items));
    }

    /**
     * @phpstan-param int $position
     * @phpstan-param iterable<T> $iterable
     *
     * @phpstan-return static<T>
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
     * @phpstan-return array<int, T>
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
     * @phpstan-return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
         return $this->createTraversable($this->items);
    }

    /**
     * @phpstan-return ArrayList<T>
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
     * @phpstan-return Map<mixed, mixed>
     */
    public function toMap(): Map
    {
        /** @phpstan-var array<array{0: mixed, 1: mixed}> */
        $pairs = $this->toArray();
        return Map::fromIterable($pairs);
    }

    /**
     * @phpstan-param iterable<T> $iterable
     * @phpstan-return Traversable<T>
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
        return (static function (iterable $iterable): Generator {
            yield from $iterable;
        })($iterable);
    }

    /**
     * Upgrades callable to accept and return `self` as arguments.
     *
     * @phpstan-param callable $callable
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
     * @phpstan-param iterable<self<A>> $iterable
     *
     * @phpstan-return self<ArrayList<A>>
     */
    final public static function sequence(iterable $iterable): self
    {
        /**
         * @phpstan-var callable(self<A>): self<A> $identity
         */
        $identity = static function ($a) {
            return $a;
        };
        return self::traverse($iterable, $identity);
    }
}
