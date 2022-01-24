<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

/** @implements Monoid<int> */
class IntSumMonoid implements Monoid
{
    public function concat($a, $b): int
    {
        return $a + $b;
    }

    public function getEmpty(): int
    {
        return 0;
    }
}
