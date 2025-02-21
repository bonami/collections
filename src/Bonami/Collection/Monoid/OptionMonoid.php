<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

use Bonami\Collection\Option;

/**
 * Wraps monoid into Option monoid. If any value is None, the result is None.
 *
 * @template T
 *
 * @implements Monoid<Option<T>>
 */
class OptionMonoid implements Monoid
{
    /** @var Monoid<T> */
    private $monoid;

    /** @param Monoid<T> $monoid */
    public function __construct(Monoid $monoid)
    {
        $this->monoid = $monoid;
    }

    /**
     * Concats two options. If any of them is None, the result is None.
     *
     * @param Option<T> $a
     * @param Option<T> $b
     *
     * @return Option<T>
     */
    public function concat($a, $b): Option
    {
        return Option::lift(fn ($a, $b) => $this->monoid->concat($a, $b))($a, $b);
    }

    /** @return Option<T> */
    public function getEmpty(): Option
    {
        return Option::some($this->monoid->getEmpty());
    }
}
