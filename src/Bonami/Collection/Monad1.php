<?php

declare(strict_types=1);

namespace Bonami\Collection;

/** @template T */
trait Monad1
{
    /** @use Applicative1<T> */
    use Applicative1;

    /**
     * Default implementation of ap, derived from flatMap and map. It can be overridden by concrete
     * implementation
     *
     * @template A
     * @template B
     *
     * @param self<CurriedFunction<A, B>> $closure
     * @param self<A> $argument
     *
     * @return self<B>
     */
    final public static function ap(self $closure, self $argument): self
    {
        return $closure->flatMap(static fn (CurriedFunction $c) => $argument->map(static fn ($a) => $c($a)));
    }

    /**
     * Default implementation of product, derived from flatMap and map. It can be overridden by concrete
     * implemention
     *
     * @template A
     * @template B
     *
     * @param self<A> $a
     * @param self<B> $b
     *
     * @return self<array{A,B}>
     */
    public static function product(self $a, self $b): self
    {
        return $a->flatMap(static fn ($x) => $b->map(static fn ($y) => [$x, $y]));
    }

    /**
     * Chain mapper call on Monad
     *
     * @template B
     *
     * @param callable(T, int): iterable<B> $mapper
     *
     * @return self<B>
     */
    abstract public function flatMap(callable $mapper): self;
}
