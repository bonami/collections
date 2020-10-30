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
 * @implements IteratorAggregate<int, T>
 */
abstract class Option implements IHashable, IteratorAggregate
{
    /** @var self<T>|null */
    private static $none;

    /**
     * @param T $value
     * @return self<T>
     */
    final public static function of($value): self
    {
         return self::some($value);
    }

    /**
     * @param ?T $value
     * @return self<T>
     */
    final public static function fromNullable($value): self
    {
         return $value === null ? self::none() : self::some($value);
    }

    /**
     * @return self<T>
     */
    final public static function none(): Option
    {
         return self::$none ?? self::$none = new class extends Option {

            public function isDefined(): bool
            {
                return false;
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

            public function filter(callable $predicate): Option
            {
                return $this;
            }

         /**
          * Consider calling getOrElse instead
          * @throws ValueIsNotPresentException
          *
          * @return T
          */
            public function getUnsafe()
            {
                throw new ValueIsNotPresentException("Can not get value for None");
            }

         /**
          * @template E
          * @param E $else
          * @return E
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
          * @return int|string
          */
            public function hashCode()
            {
                return spl_object_hash($this); // There should be only one instance of none
            }

         /**
          * @return Traversable<int, T>
          */
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
     * @param T $value
     * @return self<T>
     */
    final public static function some($value): self
    {
         return new class ($value) extends Option {

             /** @var T */
             private $value;

             /** @param T $value */
            protected function __construct($value)
            {
                $this->value = $value;
            }

            public function isDefined(): bool
            {
                return true;
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

            public function filter(callable $predicate): Option
            {
                return $predicate($this->value) ? $this : self::none();
            }

         /**
          * Consider calling getOrElse instead
          * @throws ValueIsNotPresentException
          *
          * @return T
          */
            public function getUnsafe()
            {
                return $this->value;
            }

         /**
          * @template E
          * @param E $else
          * @return T
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
          * @return int|string
          */
            public function hashCode()
            {
                $valueHash = $this->value instanceof IHashable
                    ? $this->value->hashCode()
                    : hashKey($this->value);
                return __CLASS__ . "::some({$valueHash})";
            }

         /**
          * @return Traversable<int, T>
          */
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

    abstract public function isDefined(): bool;

    /**
     * @param callable(T): bool $predicate
     * @return self<T>
     */
    abstract public function filter(callable $predicate): self;

    /**
     * @param self<mixed> $option
     *
     * @return self<mixed>
     */
    abstract public function ap(self $option): self;

    /**
     * @param callable(mixed): mixed $mapper
     *
     * @return self<mixed>
     */
    abstract public function map(callable $mapper): self;

    /**
     * @param callable(mixed): self<mixed> $mapper
     *
     * @return self<mixed>
     */
    abstract public function flatMap(callable $mapper): self;

    /**
     * @template R
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
     * Consider calling getOrElse instead
     * @throws ValueIsNotPresentException
     *
     * @return T
     */
    abstract public function getUnsafe();

    /**
     * @template E
     * @param E $else
     * @return T|E
     */
    abstract public function getOrElse($else);

    /**
     * @return TrySafe<T>
     */
    abstract public function toTrySafe(): TrySafe;

    /**
     * @param self<T> $else
     *
     * @return self<T>
     */
    abstract public function orElse(self $else): self;

    /**
     * @template F
     * @template S
     * @param callable(): F $handleNone
     * @param callable(T): S $handleSome
     *
     * @return F|S
     */
    abstract public function resolve(callable $handleNone, callable $handleSome);

    /**
     * @param self<T> $value
     * @return bool
     */
    final public function equals($value): bool
    {
         return $value instanceof Option
         && $value->hashCode() === $this->hashCode();
    }

    /**
     * Upgrades callable to accept and return `self` as arguments.
     *
     * @phpstan-param callable $callable
     * @return callable
     */
    final public static function lift(callable $callable): callable
    {
        return function (self ...$arguments) use ($callable): self {
            $reducer = function (self $applicative, self $argument): self {
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
                function (self $reducedApplicative, $impureItem) use ($mapperToApplicative): self {
                    $applicative = $mapperToApplicative($impureItem);
                    assert($applicative instanceof self);
                    return $reducedApplicative
                        ->map(function (ArrayList $resultIterable): callable {
                            return function ($item) use ($resultIterable): ArrayList {
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
