<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\ValueIsNotPresentException;
use Bonami\Collection\Hash\IHashable;
use EmptyIterator;
use Iterator;
use IteratorAggregate;

/**
 * @template T
 *
 * @phpstan-implements IteratorAggregate<int, T>
 */
abstract class Option implements IHashable, IteratorAggregate
{
    /** @use Monad1<T> */
    use Monad1;

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

            /**
             * @template B
             *
             * @param Option<B> $fb
             * @return Option<array{T, B}>
             */
            function product(Option $fb): Option
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

            /** @phpstan-return Iterator <int, T> */
            public function getIterator(): Iterator
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

            public function map(callable $mapper): Option
            {
                return self::of($mapper($this->value));
            }

            /**
             * @template B
             *
             * @param Option<B> $fb
             * @return Option<array{V, B}>
             */
            function product(Option $fb): Option
            {
                // @phpstan-ignore-next-line
                return $fb->map(fn ($b) => Option::some([$this->value, $b]));
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

            /** @phpstan-return Iterator<int, V> */
            public function getIterator(): Iterator
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
     * @template B
     *
     * @param self<B> $fb
     * @return self<array{T, B}>
     */
    abstract public function product(self $fb): self;

    /**
     * @template V
     *
     * @phpstan-param V $value
     *
     * @phpstan-return self<V>
     */
    final public static function of($value)
    {
        return self::some($value);
    }

    /**
     * @template V
     *
     * @phpstan-param V $value
     *
     * @phpstan-return self<V>
     */
    final public static function pure($value)
    {
        return self::some($value);
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
