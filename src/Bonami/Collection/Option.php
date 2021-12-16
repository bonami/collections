<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\ValueIsNotPresentException;
use Bonami\Collection\Hash\IHashable;
use EmptyIterator;
use IteratorAggregate;
use Traversable;

/**
 * @template T
 *
 * @phpstan-implements IteratorAggregate<int, T>
 */
abstract class Option implements IHashable, IteratorAggregate
{
    /** @phpstan-var self<T>|null */
    private static $none;

    /**
     * @template V
     *
     * @phpstan-param ?V $value
     *
     * @phpstan-return self<V>
     */
    final public static function fromNullable($value): self
    {
        return $value === null ? self::none() : self::some($value);
    }

    /** @phpstan-return self<mixed> */
    final public static function none(): Option
    {
        return self::$none ?? self::$none = new class extends Option {
            public function isDefined(): bool
            {
                return false;
            }

            public function isEmpty(): bool
            {
                return true;
            }

            public function map(callable $mapper): Option
            {
                return $this;
            }

            public function ap(Option $option): Option
            {
                return $this;
            }

            public function flatMap(callable $mapper): Option
            {
                return $this;
            }

            public function each(callable $sideEffect): void
            {
            }

            public function tapNone(callable $sideEffect): Option
            {
                $sideEffect();
                return $this;
            }

            public function filter(callable $predicate): Option
            {
                return $this;
            }

            public function exists(callable $predicate): bool
            {
                return false;
            }

            /**
             * Consider calling getOrElse instead
             *
             * @throws ValueIsNotPresentException
             *
             * @phpstan-return T
             */
            public function getUnsafe()
            {
                throw new ValueIsNotPresentException('Can not get value from None');
            }

            /**
             * @template E
             *
             * @phpstan-param E $else
             *
             * @phpstan-return T|E
             */
            public function getOrElse($else)
            {
                return $else;
            }

            public function toTrySafe(): TrySafe
            {
                return TrySafe::failure(new ValueIsNotPresentException());
            }

            /**
             * @template L
             *
             * @param L $left
             *
             * @return Either<L, T>
             */
            public function toEither($left): Either
            {
                return Either::left($left);
            }

            /** @phpstan-return int|string */
            public function hashCode()
            {
                return spl_object_hash($this); // There should be only one instance of none
            }

            /** @phpstan-return Traversable<int, T> */
            public function getIterator(): Traversable
            {
                return new EmptyIterator();
            }

            public function orElse(Option $else): Option
            {
                return $else;
            }

            public function resolve(callable $handleNone, callable $handleSome)
            {
                return $handleNone();
            }

            public function __toString(): string
            {
                return 'None';
            }
        };
    }

    /**
     * @template V
     *
     * @phpstan-param V $value
     *
     * @phpstan-return self<V>
     */
    final public static function some($value): self
    {
        return new class ($value) extends Option {
            /** @phpstan-var V */
            private $value;

            /** @phpstan-param V $value */
            protected function __construct($value)
            {
                $this->value = $value;
            }

            public function isDefined(): bool
            {
                return true;
            }

            public function isEmpty(): bool
            {
                return false;
            }

            public function ap(Option $option): Option
            {
                assert(is_callable($this->value));
                return $option->map(function ($value) {
                    return Lambda::of($this->value)($value);
                });
            }

            public function map(callable $mapper): Option
            {
                return self::of($mapper($this->value));
            }

            public function flatMap(callable $mapper): Option
            {
                $option = $mapper($this->value);
                assert($option instanceof Option);
                return $option;
            }

            public function each(callable $sideEffect): void
            {
                $sideEffect($this->value);
            }

            public function tapNone(callable $sideEffect): Option
            {
                return $this;
            }

            public function filter(callable $predicate): Option
            {
                return $predicate($this->value) ? $this : self::none();
            }

            public function exists(callable $predicate): bool
            {
                return $predicate($this->value);
            }

            /**
             * Consider calling getOrElse instead
             *
             * @throws ValueIsNotPresentException
             *
             * @phpstan-return V
             */
            public function getUnsafe()
            {
                return $this->value;
            }

            /**
             * @template E
             *
             * @phpstan-param E $else
             *
             * @phpstan-return V|E
             */
            public function getOrElse($else)
            {
                return $this->value;
            }

            public function toTrySafe(): TrySafe
            {
                return TrySafe::success($this->value);
            }

            /**
             * @template L
             *
             * @param L $left
             *
             * @return Either<L, V>
             */
            public function toEither($left): Either
            {
                return Either::right($this->value);
            }

            /** @phpstan-return int|string */
            public function hashCode()
            {
                $valueHash = $this->value instanceof IHashable
                    ? $this->value->hashCode()
                    : hashKey($this->value);
                return sprintf('%s::some(%s)', self::class, $valueHash);
            }

            /** @phpstan-return Traversable<int, V> */
            public function getIterator()
            {
                return new ArrayIterator([$this->value]);
            }

            public function orElse(Option $else): Option
            {
                return $this;
            }

            public function resolve(callable $handleNone, callable $handleSome)
            {
                return $handleSome($this->value);
            }

            public function __toString(): string
            {
                return 'Some(' . $this->value . ')';
            }
        };
    }

    /**
     * @template B
     *
     * @phpstan-param callable(T): B $mapper
     *
     * @phpstan-return self<B>
     */
    abstract public function map(callable $mapper): self;

    /**
     * @template V
     *
     * @phpstan-param V $value
     *
     * @phpstan-return self<V>
     */
    final public static function of($value): self
    {
        return self::some($value);
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
     * @phpstan-param self<mixed> $option
     *
     * @phpstan-return self<mixed>
     */
    abstract public function ap(self $option): self;

    /**
     * Takes any `iterable<self<A>>` and sequence it into `self<ArrayList<A>>`. If any `self` is "empty", the result is
     * "short circuited".
     *
     * When any instance is a None, then result is None.
     * If all instances are Some, the result is Some<ArrayList<A>>
     *
     * @template A
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

    /**
     * Takes any `iterable<A>`, for each item `A` transforms to applicative with $mapperToApplicative
     * `A => self<B>` and cumulates it in `self<ArrayList<B>>`.
     *
     * @see sequence - behaves same as traverse, execept it is called with identity
     *
     * @template A
     * @template B
     *
     * @phpstan-param iterable<A> $iterable
     * @phpstan-param callable(A): self<B> $mapperToApplicative
     *
     * @phpstan-return self<ArrayList<B>>
     */
    final public static function traverse(iterable $iterable, callable $mapperToApplicative): self
    {
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

    abstract public function isDefined(): bool;

    abstract public function isEmpty(): bool;

    /**
     * @phpstan-param callable(T): bool $predicate
     *
     * @phpstan-return self<T>
     */
    abstract public function filter(callable $predicate): self;

    /**
     * @phpstan-param callable(T): bool $predicate
     *
     * @phpstan-return bool
     */
    abstract public function exists(callable $predicate): bool;

    /**
     * @template B
     *
     * @phpstan-param callable(T): self<B> $mapper
     *
     * @phpstan-return self<B>
     */
    abstract public function flatMap(callable $mapper): self;

    /**
     * @template R
     *
     * @phpstan-param callable(R, T): R $reducer
     * @phpstan-param R $initialReduction
     *
     * @phpstan-return R
     */
    final public function reduce(callable $reducer, $initialReduction)
    {
        return LazyList::fromIterable($this)->reduce($reducer, $initialReduction);
    }

    /** @phpstan-param callable(T): void $sideEffect */
    abstract public function each(callable $sideEffect): void;

    /**
     * Executes $sideEffect if Option is some and ignores it for none. Then returns Option unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(1)
     *
     * @phpstan-param callable(T): void $sideEffect
     *
     * @phpstan-return self<T>
     */
    public function tap(callable $sideEffect): self
    {
        foreach ($this as $item) {
            $sideEffect($item);
        }

        return $this;
    }

    /**
     * Executes $sideEffect if Option is none and ignores it for some. Then returns Option unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects when you want to react on missing value (like logging)
     *
     * Complexity: o(1)
     *
     * @phpstan-param callable(): void $sideEffect
     *
     * @phpstan-return self<T>
     */
    abstract public function tapNone(callable $sideEffect): self;

    /**
     * Consider calling getOrElse instead
     *
     * @throws ValueIsNotPresentException
     *
     * @phpstan-return T
     */
    abstract public function getUnsafe();

    /**
     * @template E
     *
     * @phpstan-param E $else
     *
     * @phpstan-return T|E
     */
    abstract public function getOrElse($else);

    /** @phpstan-return TrySafe<T> */
    abstract public function toTrySafe(): TrySafe;

    /**
     * @template L
     *
     * @param L $left
     *
     * @phpstan-return Either<L, T>
     */
    abstract public function toEither($left): Either;

    /** @return ArrayList<T> */
    public function toList(): ArrayList
    {
        return ArrayList::fromIterable($this);
    }

    /** @return array<T> */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * @phpstan-param self<T> $else
     *
     * @phpstan-return self<T>
     */
    abstract public function orElse(self $else): self;

    /**
     * @template B
     *
     * @phpstan-param callable(): B $handleNone
     * @phpstan-param callable(T): B $handleSome
     *
     * @phpstan-return B
     */
    abstract public function resolve(callable $handleNone, callable $handleSome);

    /**
     * @phpstan-param self<T> $value
     *
     * @phpstan-return bool
     */
    final public function equals($value): bool
    {
        return $value instanceof self
            && $value->hashCode() === $this->hashCode();
    }
}
