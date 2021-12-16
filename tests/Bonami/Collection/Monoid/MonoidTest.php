<?php

declare(strict_types=1);

namespace Bonami\Collection\Monoid;

use Bonami\Collection\Option;
use PHPUnit\Framework\TestCase;

class MonoidTest extends TestCase
{
    /**
     * @dataProvider provideFixtures
     *
     * @template A
     *
     * @phpstan-param A $a
     * @phpstan-param A $b
     * @phpstan-param Monoid<A> $monoid
     * @phpstan-param A $result
     */
    public function testFromMonoids($a, $b, Monoid $monoid, $result): void
    {
        self::assertEquals($a, $monoid->concat($a, $monoid->getEmpty()));
        self::assertEquals($a, $monoid->concat($monoid->getEmpty(), $a));
        self::assertEquals($result, $monoid->concat($a, $b));
    }

    /** @phpstan-return iterable<array{0: mixed, 1: mixed, 2: mixed, 3: mixed}> */
    public function provideFixtures(): iterable
    {
        yield [1, 2, new IntSumMonoid(), 3];
        yield [1, 2, new IntProductMonoid(), 2];
        yield [1.1, 2.1, new DoubleSumMonoid(), 3.2];
        yield [1.1, 2.1, new DoubleProductMonoid(), 2.31];
        yield ['foo', 'bar', new StringMonoid(), 'foobar'];
        yield [Option::none(), Option::some(1), new OptionMonoid(new IntSumMonoid()), Option::none()];
        yield [Option::some(1), Option::some(2), new OptionMonoid(new IntSumMonoid()), Option::some(3)];
    }
}
