<?php

declare(strict_types=1);

namespace Bonami\Collection;

/**
 * @template L
 * @template R
 */
trait Applicative2
{
    /**
     * @template A
     *
     * @phpstan-param A $value
     *
     * @phpstan-return static<A>
     */
    abstract public static function pure($value);

    /**
     * @template A
     * @template B
     *
     * @param self<L, callable(A): B> $closure
     * @param self<L, A> $argument
     *
     * @return self<L, B>
     */
    abstract public static function ap(self $closure, self $argument): self;

    /**
     * @template A
     *
     * @param callable(R): A $mapper
     *
     * @phpstan-return self<L, A>
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
     * Takes any `iterable<self<L, R>>` and sequence it into `self<L, ArrayList<A>>`.
     * If any `self` is "empty", the result is "short circuited".
     *
     * @template A
     *
     * @phpstan-param iterable<self<L, A>> $iterable
     *
     * @phpstan-return self<L, ArrayList<A>>
     */
    final public static function sequence(iterable $iterable): self
    {
        // @phpstan-ignore-next-line
        return self::traverse($iterable, identity());
    }

    /**
     * Takes any `iterable<A>`, for each item `A` transforms to applicative with $mapperToApplicative
     * `A => self<L, B>` and cumulates it in `self<L, ArrayList<B>>`.
     *
     * @see sequence - behaves same as traverse, execept it is called with identity
     *
     * @template A
     * @template B
     *
     * @phpstan-param iterable<A> $iterable
     * @phpstan-param callable(A): self<L, B> $mapperToApplicative
     *
     * @phpstan-return self<L, ArrayList<B>>
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
