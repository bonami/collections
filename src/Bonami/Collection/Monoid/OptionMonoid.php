<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

use Bonami\Collection\Option;

/**
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
     * @param Option<T> $a
     * @param Option<T> $b
     *
     * @return Option<T>
     */
    public function concat($a, $b): Option
    {
        return Option::lift(function ($a, $b) {
            return $this->monoid->concat($a, $b);
        })($a, $b);
    }

    /** @return Option<T> */
    public function getEmpty(): Option
    {
        return Option::some($this->monoid->getEmpty());
    }
}
