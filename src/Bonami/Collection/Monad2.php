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
     * @template B
     *
     * @phpstan-param callable(R, int): iterable<B> $mapper
     *
     * @phpstan-return self<L, B>
     */
    abstract public function flatMap(callable $mapper): self;
}
