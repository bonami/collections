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
     * @phpstan-param callable(T, int): iterable<B> $mapper
     *
     * @phpstan-return self<B>
     */
    abstract public function flatMap(callable $mapper): self;

    /**
     * Default implementation of product, derived from flatMap and map. It can be overridden by concrete
     * implementation
     *
     * @template B
     *
     * @param self<B> $fb
     *
     * @return self<array{0: T, 1: B}> $argument
     */
    public function product(self $fb): self
    {
        // @phpstan-ignore-next-line
        return $this->flatMap(static fn($a) => $fb->map(static fn($b) => [$a, $b]));
    }
}
