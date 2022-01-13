<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

/** @template A */
interface Monoid
{
    /**
     * A binary operation.
     *
     * It must follow monoid laws:
     * - associativity - `concat(concat($a, $b), $c) === concat($a, concat($b, $c))` for any element `A`
     * - identity law - `concat($a, getEmpty()) === concat(getEmpty(), $a) === $a`
     *
     * @param A $a
     * @param A $b
     *
     * @return A
     */
    public function concat($a, $b);

    /**
     * Neutral element for binary concat operation
     *
     * @return A
     */
    public function getEmpty();
}
