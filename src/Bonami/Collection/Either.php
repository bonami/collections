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
 * A generic structure for a type with two possibilities: a Either::left(L) or a Either::right(R).
 *
 * Also known as sum type (to describe choice from two values).
 *
 * Usually used for representing successful result in Right (R) or error in Left (L).
 *
 * It is also right-biased monad (meaning, that flatMap, map and so on operates on Right side
 * and short circuits on left side).
 *
 * Either also implements some bifunctor methods, such as mapLeft and flatMapLeft to operate on Left side.
 *
 * @template L
 * @template R
 *
 * @implements IteratorAggregate<int, R>
 */
abstract class Either implements IHashable, IteratorAggregate
{
    /** @use Monad2<L, R> */
    use Monad2;

    /**
     * @param L $left
     *
     * @return self<L, R>
     */
    final public static function left($left): self
    {
        return new class ($left) extends Either {
            /** @var L */
            private $left;

            /** @param L $left */
            protected function __construct($left)
            {
                $this->left = $left;
            }

            public function isLeft(): bool
            {
                return true;
            }

            public function isRight(): bool
            {
                return false;
            }

            public function mapLeft(callable $mapper): Either
            {
                return self::left($mapper($this->left));
            }

            public function each(callable $sideEffect): void
            {
            }

            public function tapLeft(callable $sideEffect): Either
            {
                $sideEffect($this->left);

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
             * @return L
             */
            public function getLeftUnsafe()
            {
                return $this->left;
            }

            /**
             * Consider calling getOrElse instead
             *
             * @throws ValueIsNotPresentException
             *
             * @return R
             */
            public function getRightUnsafe()
            {
                throw new ValueIsNotPresentException('Can not get Right value from Left');
            }

            public function resolve(callable $handleLeft, callable $handleRight)
            {
                return $handleLeft($this->left);
            }

            /**
             * @template E
             *
             * @param E $else
             *
             * @return R|E
             */
            public function getOrElse($else)
            {
                return $else;
            }

            public function toTrySafe(): TrySafe
            {
                return TrySafe::failure(new ValueIsNotPresentException());
            }

            public function hashCode(): string
            {
                $valueHash = $this->left instanceof IHashable
                    ? $this->left->hashCode()
                    : hashKey($this->left);
                return sprintf('%s::left(%s)', self::class, $valueHash);
            }

            /** @return Iterator<int, R> */
            public function getIterator(): Iterator
            {
                return new EmptyIterator();
            }

            public function orElse(Either $else): Either
            {
                return $else;
            }

            public function equals($other): bool
            {
                return $other instanceof self && $other->left === $this->left;
            }

            public function __toString(): string
            {
                return 'Either::left(' . $this->left . ')';
            }

            public function toOption(): Option
            {
                return Option::none();
            }

            public function switch(): Either
            {
                return Either::right($this->left);
            }
        };
    }

    /**
     * @template V
     *
     * @param V $right
     *
     * @return self<L, V>
     */
    final public static function right($right): self
    {
        return new class ($right) extends Either {
            /** @var V */
            private $right;

            /** @param V $right */
            protected function __construct($right)
            {
                $this->right = $right;
            }

            public function isLeft(): bool
            {
                return false;
            }

            public function isRight(): bool
            {
                return true;
            }

            public function mapLeft(callable $mapper): Either
            {
                return $this;
            }

            public function each(callable $sideEffect): void
            {
                $sideEffect($this->right);
            }

            public function tapLeft(callable $sideEffect): Either
            {
                return $this;
            }

            public function exists(callable $predicate): bool
            {
                return $predicate($this->right);
            }

            /**
             * Consider calling getOrElse instead
             *
             * @throws ValueIsNotPresentException
             *
             * @return L
             */
            public function getLeftUnsafe()
            {
                throw new ValueIsNotPresentException('Can not get Left value from Right');
            }

            /**
             * Consider calling getOrElse instead
             *
             * @throws ValueIsNotPresentException
             *
             * @return V
             */
            public function getRightUnsafe()
            {
                return $this->right;
            }

            public function resolve(callable $handleLeft, callable $handleRight)
            {
                return $handleRight($this->right);
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
                return $this->right;
            }

            public function toTrySafe(): TrySafe
            {
                return TrySafe::success($this->right);
            }

            public function hashCode(): string
            {
                $valueHash = $this->right instanceof IHashable
                    ? $this->right->hashCode()
                    : hashKey($this->right);
                return sprintf('%s::right(%s)', self::class, $valueHash);
            }

            /** @return Iterator<int, V> */
            public function getIterator(): Iterator
            {
                return new ArrayIterator([$this->right]);
            }

            public function orElse(Either $else): Either
            {
                return $this;
            }

            public function equals($other): bool
            {
                return $other instanceof self && $other->right === $this->right;
            }

            public function __toString(): string
            {
                return 'Either::right(' . $this->right . ')';
            }

            public function toOption(): Option
            {
                return Option::some($this->right);
            }

            public function switch(): Either
            {
                return Either::left($this->right);
            }
        };
    }

    /**
     * @template A
     *
     * @param callable(R): A $mapper
     *
     * @return self<L, A>
     */
    public function map(callable $mapper): self
    {
        return $this->isRight()
            ? self::right($mapper($this->getRightUnsafe()))
            : $this;
    }

    /**
     * @template B
     *
     * @param callable(L): B $mapper
     *
     * @return self<B, R>
     */
    abstract public function mapLeft(callable $mapper): self;

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<L, V>
     */
    final public static function of($value): self
    {
        return self::right($value);
    }

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<L, V>
     */
    final public static function pure($value): self
    {
        return self::right($value);
    }

    abstract public function isRight(): bool;

    abstract public function isLeft(): bool;

    /**
     * @param callable(R): bool $predicate
     *
     * @return bool
     */
    abstract public function exists(callable $predicate): bool;

    /**
     * @template B
     *
     * @param callable(R): self<L, B> $mapper
     *
     * @return self<L, B>
     */
    public function flatMap(callable $mapper): self
    {
        return $this->isRight()
            ? $mapper($this->getRightUnsafe())
            : $this;
    }

    /**
     * @template B
     *
     * @param callable(L): self<B, R> $mapper
     *
     * @return self<B, R>
     */
    public function flatMapLeft(callable $mapper): self
    {
        return $this->isLeft()
            ? $mapper($this->getLeftUnsafe())
            : $this;
    }

    /**
     * @template A
     *
     * @param callable(A, R): A $reducer
     * @param A $initialReduction
     *
     * @return A
     */
    final public function reduce(callable $reducer, $initialReduction)
    {
        return LazyList::fromIterable($this)->reduce($reducer, $initialReduction);
    }

    /** @param callable(R): void $sideEffect */
    abstract public function each(callable $sideEffect): void;

    /**
     * Executes $sideEffect if Either is right and ignores it for left. Then returns Either unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(1)
     *
     * @param callable(R): void $sideEffect
     *
     * @return self<L, R>
     */
    public function tap(callable $sideEffect): self
    {
        foreach ($this as $item) {
            $sideEffect($item);
        }

        return $this;
    }

    /**
     * Executes $sideEffect if Either is left and ignores it for right. Then returns Either unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(1)
     *
     * @param callable(L): void $sideEffect
     *
     * @return self<L, R>
     */
    abstract public function tapLeft(callable $sideEffect): self;

    /**
     * Consider calling getOrElse instead
     *
     * @throws ValueIsNotPresentException
     *
     * @return L
     */
    abstract public function getLeftUnsafe();

    /**
     * Consider calling getOrElse instead
     *
     * @throws ValueIsNotPresentException
     *
     * @return R
     */
    abstract public function getRightUnsafe();

    /**
     * @template B
     *
     * @param callable(L): B $handleLeft
     * @param callable(R): B $handleRight
     *
     * @return B
     */
    abstract public function resolve(callable $handleLeft, callable $handleRight);

    /**
     * @template E
     *
     * @param E $else
     *
     * @return R|E
     */
    abstract public function getOrElse($else);

    /**
     * Converts Either to TrySafe.
     *
     * Left value is dropped and replaced with exception
     * ValueIsNotPresentException wrapped in `TrySafe::failure`
     *
     * Right value is preserved and wrapped into `TrySafe::success`
     *
     * @return TrySafe<R>
     */
    abstract public function toTrySafe(): TrySafe;

    /**
     * Converts Either to Option.
     *
     * Right value is preserved and wrapped into `Option::some`
     *
     * Left value is dropped and replaced with `Option::none`
     *
     * @return Option<R>
     */
    abstract public function toOption(): Option;

    /**
     * @param self<L, R> $else
     *
     * @return self<L, R>
     */
    abstract public function orElse(self $else): self;

    /**
     * @param self<L, R> $other
     *
     * @return bool
     */
    abstract public function equals(self $other): bool;

    /**
     * Switches left and right. This can be useful when you need to operate with
     * left side same way as it was monad (e.g. flat mapping)
     *
     * @return self<R, L>
     */
    abstract public function switch(): self;
}
