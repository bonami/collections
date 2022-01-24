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
        return $closure->flatMap(static function (CurriedFunction $c) use ($argument) {
            return $argument->map(static function ($a) use ($c) {
                return $c($a);
            });
        });
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
