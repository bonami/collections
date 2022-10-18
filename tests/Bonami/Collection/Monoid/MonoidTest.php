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
     * @param A $a
     * @param A $b
     * @param Monoid<A> $monoid
     * @param A $result
     * @param ?float $precision
     */
    public function testFromMonoids($a, $b, Monoid $monoid, $result, ?float $precision): void
    {
        if ($precision === null) {
            self::assertEquals($a, $monoid->concat($a, $monoid->getEmpty()));
            self::assertEquals($a, $monoid->concat($monoid->getEmpty(), $a));
            self::assertEquals($result, $monoid->concat($a, $b));
        } else {
            self::assertEqualsWithDelta($a, $monoid->concat($a, $monoid->getEmpty()), $precision);
            self::assertEqualsWithDelta($a, $monoid->concat($monoid->getEmpty(), $a), $precision);
            self::assertEqualsWithDelta($result, $monoid->concat($a, $b), $precision);
        }
    }

    /** @phpstan-return iterable<array{0: mixed, 1: mixed, 2: mixed, 3: mixed}> */
    public function provideFixtures(): iterable
    {
        yield [1, 2, new IntSumMonoid(), 3, null];
        yield [1, 2, new IntProductMonoid(), 2, null];
        yield [1.1, 2.1, new DoubleSumMonoid(), 3.2, 0.001];
        yield [1.1, 2.1, new DoubleProductMonoid(), 2.31, 0.001];
        yield ['foo', 'bar', new StringMonoid(), 'foobar', null];
        yield [Option::none(), Option::some(1), new OptionMonoid(new IntSumMonoid()), Option::none(), null];
        yield [Option::some(1), Option::some(2), new OptionMonoid(new IntSumMonoid()), Option::some(3), null];
    }
}
