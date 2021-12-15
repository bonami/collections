<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

/** @phpstan-implements Monoid<double> */
class DoubleSumMonoid implements Monoid
{
    public function concat($a, $b)
    {
        return $a + $b;
    }

    public function getEmpty()
    {
        return 0;
    }
}
