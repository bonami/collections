<?php

declare(strict_types=1);

namespace Bonami\Collection;

/**
 * @template L
 * @template R
 */
trait Monad2
{
    /** @use Applicative2<L, R> */
    use Applicative2;

    /**
     * Default implementation of ap, derived from flatMap and map. It can be overridden by concrete
     * implementation
     *
     * @template A
     * @template B
     *
     * @param self<L, CurriedFunction<A, B>> $closure
     * @param self<L, A> $argument
     *
     * @return self<L, B>
     */
    final public static function ap(self $closure, self $argument): self
    {
        return $closure->flatMap(static function ($c) use ($argument) {
            return $argument->map(static function ($a) use ($c) {
                return $c($a);
            });
        });
    }

    /**
     * Default implementation of product, derived from flatMap and map. It can be overridden by concrete
     * implemention
     *
     * @template A
     * @template B
     *
     * @param self<L, A> $a
     * @param self<L, B> $b
     *
     * @return self<L, array{A,B}>
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
     * @param callable(R, int): iterable<B> $mapper
     *
     * @return self<L, B>
     */
    abstract public function flatMap(callable $mapper): self;
}
