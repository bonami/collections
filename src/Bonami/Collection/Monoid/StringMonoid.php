<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

/** @phpstan-implements Monoid<string> */
class StringMonoid implements Monoid
{
    public function concat($a, $b): string
    {
        return $a . $b;
    }

    public function getEmpty(): string
    {
        return '';
    }
}
