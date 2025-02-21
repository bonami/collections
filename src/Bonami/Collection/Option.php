<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\ValueIsNotPresentException;
use Bonami\Collection\Hash\IHashable;
use EmptyIterator;
use Iterator;
use IteratorAggregate;
use Throwable;

/**
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
abstract class Option implements IHashable, IteratorAggregate
{
    /** @use Monad1<T> */
    use Monad1;
    /** @use Iterable1<T> */
    use Iterable1;

    /** @var self<T>|null */
    private static $none;

    /**
     * @template V
     *
     * @param ?V $value
     *
     * @return self<V>
     */
    final public static function fromNullable($value): self
    {
        return $value === null ? self::none() : self::some($value);
    }

    /** @return self<mixed> */
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

            /**
             * Consider calling getOrElse instead
             *
             * @throws ValueIsNotPresentException
             *
             * @return T
             */
            public function getUnsafe()
            {
                throw new ValueIsNotPresentException('Can not get value from None');
            }

            /**
             * @template E
             *
             * @param E $else
             *
             * @return T|E
             */
            public function getOrElse($else)
            {
                return $else;
            }

            public function getOrElseLazy($else)
            {
                return $else();
            }

            public function getOrThrow(callable $throw)
            {
                throw $throw();
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

            public function hashCode(): string
            {
                return spl_object_hash($this); // There should be only one instance of none
            }

            /** @return Iterator <int, T> */
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
     * @param V $value
     *
     * @return self<V>
     */
    final public static function some($value): self
    {
        return new class ($value) extends Option {
            /** @var V */
            private $value;

            /** @param V $value */
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

            /**
             * Consider calling getOrElse instead
             *
             * @return V
             */
            public function getUnsafe()
            {
                return $this->value;
            }

            /**
             * @template E
             *
             * @param E $else
             *
             * @return V|E
             */
            public function getOrElse($else)
            {
                return $this->value;
            }

            public function getOrElseLazy($else)
            {
                return $this->value;
            }

            public function getOrThrow(callable $throw)
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

            public function hashCode(): string
            {
                $valueHash = $this->value instanceof IHashable
                    ? $this->value->hashCode()
                    : hashKey($this->value);
                return sprintf('%s::some(%s)', self::class, $valueHash);
            }

            /** @return Iterator<int, V> */
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
     * @param callable(T): B $mapper
     *
     * @return self<B>
     */
    public function map(callable $mapper): self
    {
        return $this->isEmpty() ? $this : self::some($mapper($this->getUnsafe()));
    }

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<V>
     */
    final public static function of($value)
    {
        return self::some($value);
    }

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<V>
     */
    final public static function pure($value)
    {
        return self::some($value);
    }

    abstract public function isDefined(): bool;

    abstract public function isEmpty(): bool;

    /**
     * @param callable(T): bool $predicate
     *
     * @return self<T>
     */
    abstract public function filter(callable $predicate): self;

    /**
     * @template B
     *
     * @param callable(T): self<B> $mapper
     *
     * @return self<B>
     */
    public function flatMap(callable $mapper): self
    {
        return $this->isEmpty() ? self::none() : $mapper($this->getUnsafe());
    }

    /**
     * @template R
     *
     * @param callable(R, T): R $reducer
     * @param R $initialReduction
     *
     * @return R
     */
    final public function reduce(callable $reducer, $initialReduction)
    {
        return LazyList::fromIterable($this)->reduce($reducer, $initialReduction);
    }

    /** @param callable(T): void $sideEffect */
    abstract public function each(callable $sideEffect): void;

    /**
     * Executes $sideEffect if Option is some and ignores it for none. Then returns Option unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(1)
     *
     * @param callable(T): void $sideEffect
     *
     * @return self<T>
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
     * @param callable(): void $sideEffect
     *
     * @return self<T>
     */
    abstract public function tapNone(callable $sideEffect): self;

    /**
     * Consider calling getOrElse instead
     *
     * @throws ValueIsNotPresentException
     *
     * @return T
     */
    abstract public function getUnsafe();

    /**
     * @template E
     *
     * @param E $else
     *
     * @return T|E
     */
    abstract public function getOrElse($else);

    /**
     * @template E
     *
     * @param callable(): E $else
     *
     * @return T|E
     */
    abstract public function getOrElseLazy($else);

    /**
     * @param callable(): Throwable $throw
     *
     * @return T
     */
    abstract public function getOrThrow(callable $throw);

    /** @return TrySafe<T> */
    abstract public function toTrySafe(): TrySafe;

    /**
     * @template L
     *
     * @param L $left
     *
     * @return Either<L, T>
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
     * @template E
     *
     * @param self<E> $else
     *
     * @return self<T|E>
     */
    abstract public function orElse(self $else): self;

    /**
     * @template B
     *
     * @param callable(): B $handleNone
     * @param callable(T): B $handleSome
     *
     * @return B
     */
    abstract public function resolve(callable $handleNone, callable $handleSome);

    /**
     * @param self<T> $value
     *
     * @return bool
     */
    final public function equals(self $value): bool
    {
        return $value->hashCode() === $this->hashCode();
    }
}
