<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

use Bonami\Collection\Option;

/**
 * @template T
 *
 * @phpstan-implements Monoid<Option<T>>
 */
class OptionMonoid implements Monoid
{

    /** @phpstan-var Monoid<T> */
    private $monoid;

    /** @phpstan-param Monoid<T> $monoid */
    public function __construct(Monoid $monoid)
    {
        $this->monoid = $monoid;
    }

    /**
     * @phpstan-param Option<T> $a
     * @phpstan-param Option<T> $b
     *
     * @phpstan-return Option<T>
     */
    public function concat($a, $b)
    {
        return Option::lift(function ($a, $b) {
            return $this->monoid->concat($a, $b);
        })($a, $b);
    }

    /** @phpstan-return Option<T> */
    public function getEmpty()
    {
        return Option::some($this->monoid->getEmpty());
    }
}
