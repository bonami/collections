<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

/** @phpstan-implements Monoid<int> */
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
