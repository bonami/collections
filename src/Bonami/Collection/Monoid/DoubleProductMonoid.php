<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

/** @implements Monoid<double> */
class DoubleProductMonoid implements Monoid
{
    public function concat($a, $b)
    {
        return $a * $b;
    }

    public function getEmpty()
    {
        return 1;
    }
}
