<?php

declare(strict_types=1);

namespace Bonami\Collection;

use PHPUnit\Framework\TestCase;

class CurriedFunctionTest extends TestCase
{
    public function testCurry(): void
    {
        $curried = CurriedFunction::curry2(static fn (int $a, int $b): int => $a + $b);

        $plus5 = $curried(5);

        self::assertEquals(42, $plus5(37));
    }

    public function testCurryN(): void
    {
        $curried = CurriedFunction::curry3(static fn (string $greeting, string $name, int $times): string => str_repeat(sprintf('%s %s,', $greeting, $name), $times));

        self::assertEquals('Hello World,Hello World,', $curried('Hello')('World')(2));
    }

    public function testMap(): void
    {
        $greeter = CurriedFunction::of(static fn(string $name): string => sprintf('Hello %s', $name));
        $countChars = CurriedFunction::of(strlen(...));

        self::assertEquals(11, CurriedFunction::of($greeter)->map($countChars)('World'));
    }

    public function testItShouldNotRewrapAlreadyWrapped(): void
    {
        $curried = CurriedFunction::curry2(static fn (int $a, int $b): int => $a + $b);

        $plus5 = $curried(5);
        self::assertSame($plus5, CurriedFunction::of($plus5));
    }
}
