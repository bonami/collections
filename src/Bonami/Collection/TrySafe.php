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
abstract class TrySafe implements IHashable, IteratorAggregate
{
    /** @use Monad1<T> */
    use Monad1;
    /** @use Iterable1<T> */
    use Iterable1;

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<V>
     */
    final public static function of($value): self
    {
        return self::success($value);
    }

    /**
     * @template V
     *
     * @param V $value
     *
     * @return self<V>
     */
    final public static function pure($value): self
    {
        return self::success($value);
    }

    /**
     * @template V
     *
     * @param callable(): V $callable
     *
     * @return self<V>
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
     * @param V $value
     *
     * @return self<V>
     */
    final public static function success($value): self
    {
        /** @extends TrySafe<V> */
        return new class ($value) extends TrySafe {
            /** @var V */
            private $value;

            /** @param V $value */
            protected function __construct($value)
            {
                $this->value = $value;
            }

            public function isSuccess(): bool
            {
                return true;
            }

            public function mapFailure(callable $exceptionMapper): TrySafe
            {
                return $this;
            }

            /** @inheritDoc */
            public function map(callable $mapper): TrySafe
            {
                return self::fromCallable(function () use ($mapper) {
                    return $mapper($this->value);
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

            /** @return V */
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

            /** @return Iterator<int, V> */
            public function getIterator(): Iterator
            {
                return new ArrayIterator([$this->value]);
            }

            public function hashCode(): string
            {
                $valueHash = $this->value instanceof IHashable
                    ? $this->value->hashCode()
                    : hashKey($this->value);
                return sprintf('%s::success(%s)', self::class, $valueHash);
            }
        };
    }

    /**
     * @param Throwable $failure
     *
     * @return self<mixed>
     */
    final public static function failure(Throwable $failure): TrySafe
    {
        /** @extends TrySafe<V> */
        return new class ($failure) extends TrySafe {
            /** @var Throwable */
            private $failure;

            protected function __construct(Throwable $failure)
            {
                $this->failure = $failure;
            }

            public function isSuccess(): bool
            {
                return false;
            }

            public function mapFailure(callable $exceptionMapper): TrySafe
            {
                return self::failure($exceptionMapper($this->failure));
            }

            public function map(callable $mapper): TrySafe
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
                return self::fromCallable(fn() => $callable($this->failure))
                    ->flatMap(static fn($x) => $x);
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
             * @return T
             */
            public function getUnsafe()
            {
                throw new ValueIsNotPresentException('Can not get value from Failure', 0, $this->failure);
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

            /** @return Iterator <int, T> */
            public function getIterator(): Iterator
            {
                return new EmptyIterator();
            }

            public function hashCode(): string
            {
                $failureHash = $this->failure instanceof IHashable
                    ? $this->failure->hashCode()
                    : hashKey($this->failure);
                return sprintf('%s::failure(%s)', self::class, $failureHash);
            }
        };
    }

    /**
     * Allow mapping failure. This can be useful when you need to translate
     * some generic exception into something more domain specific.
     *
     * @param callable(Throwable): Throwable $exceptionMapper
     *
     * @return self<T>
     */
    abstract public function mapFailure(callable $exceptionMapper): self;

    /**
     * @template B
     *
     * @param callable(T): B $mapper
     *
     * @return self<B>
     */
    abstract public function map(callable $mapper): self;

    /**
     * @template B
     *
     * @param callable(T): self<B> $mapper
     *
     * @return self<B>
     */
    abstract public function flatMap(callable $mapper): self;

    /**
     * Executes $sideEffect if TrySafe is successful and ignores it otherwise
     *
     * Complexity: o(1)
     *
     * @param callable(T): void $sideEffect
     *
     * @return void
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
     * Executes $sideEffect if TrySafe is failure and ignores it otherwise. Then returns TrySafe unchanged
     * (the very same reference)
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(1)
     *
     * @param callable(Throwable): void $sideEffect
     *
     * @return self<T>
     */
    abstract public function tapFailure(callable $sideEffect): self;

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

    /**
     * @param self<T> $value
     *
     * @return bool
     */
    final public function equals(self $value): bool
    {
        return $value->hashCode() === $this->hashCode();
    }

    /**
     * @param callable(Throwable): T $callable
     *
     * @return self<T>
     */
    abstract public function recover(callable $callable): self;

    /**
     * Runs recovery only if predicate returns true. E.g. to check type of Throwable.
     *
     * Recovery must return directly the value or throw another exception
     * (which is automatically wrapped into TrySafe)
     *
     * @param callable(Throwable): bool $predicate
     * @param callable(Throwable): T $recovery
     *
     * @return self<T>
     */
    abstract public function recoverIf(callable $predicate, callable $recovery): self;

    /**
     * @param callable(Throwable): TrySafe<T> $callable
     *
     * @return self<T>
     */
    abstract public function recoverWith(callable $callable): self;

    /**
     * Runs recovery only if predicate returns true. E.g. to check type of Throwable
     *
     * Recovery must return TrySafe::success() if the recovery is successful or TrySafe::failure() if it fails.
     * That allows chaining multiple calls with possible failure.
     *
     * @param callable(Throwable): bool $predicate - checks if conditions are met to run recovery.
     * @param callable(Throwable): TrySafe<T> $recovery - a recovery you want to try run
     *
     * @return self<T>
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

    /** @throws ValueIsNotPresentException */
    abstract public function getFailureUnsafe(): Throwable;

    /** @return Option<T> */
    abstract public function toOption(): Option;

    /** @return Either<Throwable, T> */
    abstract public function toEither(): Either;

    /**
     * @template B
     *
     * @param callable(Throwable): B $handleFailure
     * @param callable(T): B $handleSuccess
     *
     * @return B
     */
    abstract public function resolve(callable $handleFailure, callable $handleSuccess);
}
