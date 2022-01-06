<?php

declare(strict_types=1);

namespace Bonami\Collection;

/** @template T */
trait Applicative1
{
    /**
     * Wraps impure item into pure context of type class.
     *
     * @template A
     *
     * @phpstan-param A $value
     *
     * @phpstan-return static<A>
     */
    abstract public static function pure($value);

    /**
     * Applies argument to callable in context of type class.
     *
     * @template A
     * @template B
     *
     * @param self<callable(A): B> $closure
     * @param self<A> $argument
     *
     * @return self<B>
     */
    abstract public static function ap(self $closure, self $argument): self;

    /**
     * Maps over values wrapped in context of type class.
     *
     * @template A
     *
     * @phpstan-param callable(T): A $mapper
     *
     * @phpstan-return self<A>
     */
    abstract public function map(callable $mapper): self;

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
            return self::sequence($arguments)->map(static function ($args) use ($callable) {
                return $callable(...$args);
            });
        };
    }

    /**
     * Takes any `iterable<self<A>>` and sequence it into `self<ArrayList<A>>`. If any `self` is "empty", the result is
     * "empty" as well.
     *
     * @template A
     *
     * @phpstan-param iterable<self<A>> $iterable
     *
     * @phpstan-return self<ArrayList<A>>
     */
    final public static function sequence(iterable $iterable): self
    {
        // @phpstan-ignore-next-line
        return self::traverse($iterable, identity());
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
        // @phpstan-ignore-next-line
        return LazyList::fromIterable($iterable)
            ->reduce(
                static function (self $reducedApplicative, $impureItem) use ($mapperToApplicative): self {
                    $applicative = $mapperToApplicative($impureItem);
                    assert($applicative instanceof self);
                    return self::ap(
                        $reducedApplicative
                            ->map(static function (ArrayList $resultIterable): callable {
                                return CurriedFunction::of(static function ($item) use ($resultIterable): ArrayList {
                                    return $resultIterable->concat(ArrayList::of($item));
                                });
                            }),
                        $applicative
                    );
                },
                self::pure(ArrayList::fromEmpty())
            );
    }
}
