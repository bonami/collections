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
 * @phpstan-implements IteratorAggregate<int, R>
 */
abstract class Either implements IHashable, IteratorAggregate
{

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

            /** @phpstan-param L $left */
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

            public function map(callable $mapper): Either
            {
                return $this;
            }

            public function mapLeft(callable $mapper): Either
            {
                return self::left($mapper($this->left));
            }

            public function ap(Either $either): Either
            {
                return $this;
            }

            public function flatMap(callable $mapper): Either
            {
                return $this;
            }

            public function flatMapLeft(callable $mapper): Either
            {
                $either = $mapper($this->left);
                assert($either instanceof Either);
                return $either;
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
             * @phpstan-return L
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
             * @phpstan-return R
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
             * @phpstan-param E $else
             *
             * @phpstan-return R|E
             */
            public function getOrElse($else)
            {
                return $else;
            }

            public function toTrySafe(): TrySafe
            {
                return TrySafe::failure(new ValueIsNotPresentException());
            }

            /** @phpstan-return int|string */
            /** @phpstan-return int|string */
            public function hashCode()
            {
                $valueHash = $this->left instanceof IHashable
                    ? $this->left->hashCode()
                    : hashKey($this->left);
                return sprintf('%s::left(%s)', self::class, $valueHash);
            }

            /** @phpstan-return Traversable<int, R> */
            public function getIterator(): Traversable
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

            /** @phpstan-var V */
            private $right;

            /** @phpstan-param V $right */
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

            public function ap(Either $either): Either
            {
                assert(is_callable($this->right));
                return $either->map(function ($value) {
                    return Lambda::of($this->right)($value);
                });
            }

            public function map(callable $mapper): Either
            {
                return self::of($mapper($this->right));
            }

            public function mapLeft(callable $mapper): Either
            {
                return $this;
            }

            public function flatMap(callable $mapper): Either
            {
                $either = $mapper($this->right);
                assert($either instanceof Either);
                return $either;
            }

            public function flatMapLeft(callable $mapper): Either
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
             * @phpstan-return L
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
             * @phpstan-return V
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
             * @phpstan-param E $else
             *
             * @phpstan-return V|E
             */
            public function getOrElse($else)
            {
                return $this->right;
            }

            public function toTrySafe(): TrySafe
            {
                return TrySafe::success($this->right);
            }

            /** @phpstan-return int|string */
            public function hashCode()
            {
                $valueHash = $this->right instanceof IHashable
                    ? $this->right->hashCode()
                    : hashKey($this->right);
                return sprintf('%s::right(%s)', self::class, $valueHash);
            }

            /** @phpstan-return Traversable<int, V> */
            public function getIterator()
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
     * @template B
     *
     * @param callable(R): B $mapper
     *
     * @phpstan-return self<L, B>
     */
    abstract public function map(callable $mapper): self;

    /**
     * @template B
     *
     * @param callable(L): B $mapper
     *
     * @phpstan-return self<B, R>
     */
    abstract public function mapLeft(callable $mapper): self;

    /**
     * @template V
     *
     * @param V $value
     *
     * @phpstan-return self<L, V>
     */
    final public static function of($value): self
    {
        return self::right($value);
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
     * @phpstan-param self<L, mixed> $either
     *
     * @phpstan-return self<L, mixed>
     */
    abstract public function ap(self $either): self;

    /**
     * Takes any `iterable<Either<L2, R2>>` and sequence it into `Either<L2, ArrayList<R2>>`.
     * If any `Either` is left instance, the result is "short circuited" and result is left.
     *
     * @template L2
     * @template R2
     *
     * @phpstan-param iterable<self<L2, R2>> $iterable
     *
     * @phpstan-return self<L2, ArrayList<R2>>
     */
    final public static function sequence(iterable $iterable): self
    {
        /** @phpstan-var callable(self<L2, R2>): self<L2, R2> $identity */
        $identity = static function ($a) {
            return $a;
        };
        return self::traverse($iterable, $identity);
    }

    /**
     * Takes any `iterable<A>`, for each item `A` transforms to applicative with $mapperToApplicative
     * `A => Either<L2, R2>` and cumulates it in `Either<L2, ArrayList<R2>>`.
     *
     * @see sequence - behaves same as traverse, execept it is called with identity
     *
     * @template A
     * @template L2
     * @template R2
     *
     * @phpstan-param iterable<A> $iterable
     * @phpstan-param callable(A): self<L2, R2> $mapperToApplicative
     *
     * @phpstan-return self<L2, ArrayList<R2>>
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

    abstract public function isRight(): bool;

    abstract public function isLeft(): bool;

    /**
     * @phpstan-param callable(R): bool $predicate
     *
     * @phpstan-return bool
     */
    abstract public function exists(callable $predicate): bool;

    /**
     * @template B
     *
     * @phpstan-param callable(R): self<L, B> $mapper
     *
     * @phpstan-return self<L, B>
     */
    abstract public function flatMap(callable $mapper): self;

    /**
     * @template B
     *
     * @phpstan-param callable(L): self<B, R> $mapper
     *
     * @phpstan-return self<B, R>
     */
    abstract public function flatMapLeft(callable $mapper): self;

    /**
     * @template A
     *
     * @phpstan-param callable(A, R): A $reducer
     * @phpstan-param A $initialReduction
     *
     * @phpstan-return A
     */
    final public function reduce(callable $reducer, $initialReduction)
    {
        return LazyList::fromIterable($this)->reduce($reducer, $initialReduction);
    }

    /** @phpstan-param callable(R): void $sideEffect */
    abstract public function each(callable $sideEffect): void;

    /**
     * Executes $sideEffect if Either is right and ignores it for left. Then returns Either unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(1)
     *
     * @phpstan-param callable(R): void $sideEffect
     *
     * @phpstan-return self<L, R>
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
     * @phpstan-param callable(L): void $sideEffect
     *
     * @phpstan-return self<L, R>
     */
    abstract public function tapLeft(callable $sideEffect): self;

    /**
     * Consider calling getOrElse instead
     *
     * @throws ValueIsNotPresentException
     *
     * @phpstan-return L
     */
    abstract public function getLeftUnsafe();

    /**
     * Consider calling getOrElse instead
     *
     * @throws ValueIsNotPresentException
     *
     * @phpstan-return R
     */
    abstract public function getRightUnsafe();

    /**
     * @template B
     *
     * @phpstan-param callable(L): B $handleLeft
     * @phpstan-param callable(R): B $handleRight
     *
     * @phpstan-return B
     */
    abstract public function resolve(callable $handleLeft, callable $handleRight);

    /**
     * @template E
     *
     * @phpstan-param E $else
     *
     * @phpstan-return R|E
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
     * @phpstan-return TrySafe<R>
     */
    abstract public function toTrySafe(): TrySafe;

    /**
     * Converts Either to Option.
     *
     * Right value is preserved and wrapped into `Option::some`
     *
     * Left value is dropped and replaced with `Option::none`
     *
     * @phpstan-return Option<R>
     */
    abstract public function toOption(): Option;

    /**
     * @param self<L, R> $else
     *
     * @phpstan-return self<L, R>
     */
    abstract public function orElse(self $else): self;

    /**
     * @phpstan-param self<L, R> $other
     *
     * @phpstan-return bool
     */
    abstract public function equals($other): bool;

    /**
     * Switches left and right. This can be useful when you need to operate with
     * left side same way as it was monad (e.g. flat mapping)
     *
     * @return self<R, L>
     */
    abstract public function switch(): self;
}
