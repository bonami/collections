<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\ValueIsNotPresentException;
use Bonami\Collection\Hash\IHashable;
use EmptyIterator;
use IteratorAggregate;
use Throwable;
use Traversable;

/**
 * @template T
 *
 * @phpstan-implements IteratorAggregate<int, T>
 */
abstract class TrySafe implements IHashable, IteratorAggregate
{
    /**
     * @template V
     *
     * @phpstan-param V $value
     *
     * @phpstan-return self<V>
     */
    final public static function of($value): self
    {
        return self::success($value);
    }

    /**
     * @template V
     *
     * @phpstan-param callable(): V $callable
     *
     * @phpstan-return self<V>
     */
    final public static function fromCallable(callable $callable): self
    {
        try {
            return self::success($callable());
        } catch (Throwable $failure) {
            return self::failure($failure);
        }
    }

    /**
     * @template V
     *
     * @phpstan-param V $value
     *
     * @phpstan-return self<V>
     */
    final public static function success($value): self
    {
        /** @phpstan-extends TrySafe<V> */
        return new class ($value) extends TrySafe {

            /** @phpstan-var V */
            private $value;

            /** @phpstan-param V $value */
            protected function __construct($value)
            {
                $this->value = $value;
            }

            public function isSuccess(): bool
            {
                return true;
            }

            /** @inheritDoc */
            public function map(callable $mapper): TrySafe
            {
                return self::fromCallable(function () use ($mapper) {
                    return $mapper($this->value);
                });
            }

            /** @inheritDoc */
            public function ap(TrySafe $trySafe): TrySafe
            {
                assert(is_callable($this->value));
                return $trySafe->map(function ($value) {
                    return Lambda::of($this->value)($value);
                });
            }

            /** @inheritDoc */
            public function flatMap(callable $mapper): TrySafe
            {
                try {
                    $trySafe = $mapper($this->value);
                } catch (Throwable $failure) {
                    return self::failure($failure);
                }

                assert($trySafe instanceof TrySafe);
                return $trySafe;
            }

            public function tapFailure(callable $sideEffect): TrySafe
            {
                return $this;
            }

            /** @inheritDoc */
            public function recover(callable $callable): TrySafe
            {
                return $this;
            }

            /** @inheritDoc */
            public function recoverIf(callable $predicate, callable $recovery): TrySafe
            {
                return $this;
            }

            /** @inheritDoc */
            public function recoverWith(callable $callable): TrySafe
            {
                return $this;
            }

            /** @inheritDoc */
            public function recoverWithIf(callable $predicate, callable $recovery): TrySafe
            {
                return $this;
            }

            /** @phpstan-return V */
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

            /** @inheritDoc */
            public function getFailureUnsafe(): Throwable
            {
                throw new ValueIsNotPresentException('Can not get Failure from Success');
            }

            public function toOption(): Option
            {
                return Option::some($this->value);
            }

            public function toEither(): Either
            {
                return Either::right($this->value);
            }

            public function resolve(callable $handleFailure, callable $handleSuccess)
            {
                return $handleSuccess($this->value);
            }

            /** @phpstan-return Traversable<int, V> */
            public function getIterator(): Traversable
            {
                return new ArrayIterator([$this->value]);
            }

            /** @phpstan-return int|string */
            public function hashCode()
            {
                $valueHash = $this->value instanceof IHashable
                    ? $this->value->hashCode()
                    : hashKey($this->value);
                return sprintf('%s::success(%s)', self::class, $valueHash);
            }
        };
    }

    /**
     * @phpstan-param Throwable $failure
     *
     * @phpstan-return self<mixed>
     */
    final public static function failure(Throwable $failure): TrySafe
    {
        /** @phpstan-extends TrySafe<V> */
        return new class ($failure) extends TrySafe {

            /** @phpstan-var Throwable */
            private $failure;

            protected function __construct(Throwable $failure)
            {
                $this->failure = $failure;
            }

            public function isSuccess(): bool
            {
                return false;
            }

            public function map(callable $mapper): TrySafe
            {
                return $this;
            }

            public function ap(TrySafe $trySafe): TrySafe
            {
                return $this;
            }

            public function flatMap(callable $mapper): TrySafe
            {
                return $this;
            }

            public function tapFailure(callable $sideEffect): TrySafe
            {
                $sideEffect($this->failure);

                return $this;
            }

            public function recover(callable $callable): TrySafe
            {
                return self::fromCallable(function () use ($callable) {
                    return $callable($this->failure);
                });
            }

            /** @inheritDoc */
            public function recoverIf(callable $predicate, callable $recovery): TrySafe
            {
                if ($predicate($this->failure)) {
                    return $this->recover($recovery);
                }

                return $this;
            }

            /**
             * @param callable(Throwable): TrySafe<T> $callable
             *
             * @return TrySafe<T>
             */
            public function recoverWith(callable $callable): TrySafe
            {
                /** @var callable(TrySafe<T>): TrySafe<T> $id */
                $id = static function ($x) {
                    return $x;
                };
                return self::fromCallable(function () use ($callable) {
                    return $callable($this->failure);
                })->flatMap($id);
            }

            /** @inheritDoc */
            public function recoverWithIf(callable $predicate, callable $recovery): TrySafe
            {
                if ($predicate($this->failure)) {
                    return $this->recoverWith($recovery);
                }

                return $this;
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
                throw new ValueIsNotPresentException('Can not get value from Failure');
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

            public function getFailureUnsafe(): Throwable
            {
                return $this->failure;
            }

            public function toOption(): Option
            {
                return Option::none();
            }

            public function toEither(): Either
            {
                return Either::left($this->failure);
            }

            public function resolve(callable $handleFailure, callable $handleSuccess)
            {
                return $handleFailure($this->failure);
            }

            /** @phpstan-return Traversable<int, T> */
            public function getIterator(): Traversable
            {
                return new EmptyIterator();
            }

            /** @phpstan-return int|string */
            public function hashCode()
            {
                $failureHash = $this->failure instanceof IHashable
                    ? $this->failure->hashCode()
                    : hashKey($this->failure);
                return sprintf('%s::failure(%s)', self::class, $failureHash);
            }
        };
    }

    /**
     * @phpstan-param self<mixed> $trySafe
     *
     * @phpstan-return self<mixed>
     */
    abstract public function ap(self $trySafe): self;

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
     * @phpstan-param callable(T): self<B> $mapper
     *
     * @phpstan-return self<B>
     */
    abstract public function flatMap(callable $mapper): self;

    /**
     * Executes $sideEffect if TrySafe is successful and ignores it otherwise
     *
     * Complexity: o(1)
     *
     * @phpstan-param callable(T): void $sideEffect
     *
     * @phpstan-return void
     */
    public function each(callable $sideEffect): void
    {
        foreach ($this as $item) {
            $sideEffect($item);
        }
    }

    /**
     * Executes $sideEffect if TrySafe is successful and ignores it otherwise. Then returns TrySafe unchanged
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
     * Executes $sideEffect if TrySafe is failure and ignores it otherwise. Then returns TrySafe unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(1)
     *
     * @phpstan-param callable(Throwable): void $sideEffect
     *
     * @phpstan-return self<T>
     */
    abstract public function tapFailure(callable $sideEffect): self;

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

    /**
     * @phpstan-param callable(Throwable): T $callable
     *
     * @phpstan-return self<T>
     */
    abstract public function recover(callable $callable): self;

    /**
     * Runs recovery only if predicate returns true. E.g. to check type of Throwable.
     *
     * Recovery must return directly the value or throw another exception
     * (which is automatically wrapped into TrySafe)
     *
     * @phpstan-param callable(Throwable): bool $predicate
     * @phpstan-param callable(Throwable): T $recovery
     *
     * @phpstan-return self<T>
     */
    abstract public function recoverIf(callable $predicate, callable $recovery): self;

    /**
     * @phpstan-param callable(Throwable): TrySafe<T> $callable
     *
     * @phpstan-return self<T>
     */
    abstract public function recoverWith(callable $callable): self;

    /**
     * Runs recovery only if predicate returns true. E.g. to check type of Throwable
     *
     * Recovery must return TrySafe::success() if the recovery is successful or TrySafe::failure() if it fails.
     * That allows chaining multiple calls with possible failure.
     *
     * @phpstan-param callable(Throwable): bool $predicate - checks if conditions are met to run recovery.
     * @phpstan-param callable(Throwable): TrySafe<T> $recovery - a recovery you want to try run
     *
     * @phpstan-return self<T>
     */
    abstract public function recoverWithIf(callable $predicate, callable $recovery): self;

    abstract public function isSuccess(): bool;

    final public function isFailure(): bool
    {
        return !$this->isSuccess();
    }

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

    /** @throws ValueIsNotPresentException */
    abstract public function getFailureUnsafe(): Throwable;

    /** @phpstan-return Option<T> */
    abstract public function toOption(): Option;

    /** @return Either<Throwable, T> */
    abstract public function toEither(): Either;

    /**
     * @template B
     *
     * @phpstan-param callable(Throwable): B $handleFailure
     * @phpstan-param callable(T): B $handleSuccess
     *
     * @phpstan-return B
     */
    abstract public function resolve(callable $handleFailure, callable $handleSuccess);

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

    /**
     * Takes any `iterable<self<A>>` and sequence it into `self<ArrayList<A>>`. If any `self` is failure, the result is
     * "short circuited" and result is first failure.
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
}
