<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidStateException;
use PHPUnit\Framework\TestCase;

class LambdaTest extends TestCase
{

    public function testCurry(): void
    {
        $curried = Lambda::of(static function (int $a, int $b): int {
            return $a + $b;
        });

        $plus5 = $curried(5);

        self::assertIsCallable($plus5);
        self::assertEquals(42, $plus5(37));
    }

    public function testCurryN(): void
    {
        $curried = Lambda::of(static function (string $greeting, string $name, int $times): string {
            return str_repeat("{$greeting} {$name},", $times);
        });

        self::assertIsCallable($curried);
        self::assertIsCallable($curried("Hello"));
        self::assertIsCallable($curried("Hello")("World"));
        self::assertEquals("Hello World,Hello World,", $curried("Hello")("World")(2));

        self::assertIsCallable($curried("Hello", "World"));
        self::assertEquals("Hello World,Hello World,", $curried("Hello", "World")(2));

        self::assertEquals("Hello World,Hello World,", $curried("Hello", "World", 2));
    }

    public function testCurryVarArg(): void
    {
        $curried = Lambda::of(static function (string $greeting, int ...$ints): string {
            return $greeting . join(',', $ints);
        });

        self::assertIsCallable($curried);
        self::assertIsCallable($curried("Hello"));
        self::assertEquals("Hello1,2,3", $curried("Hello")(1, 2, 3));
    }

    public function testMap(): void
    {
        $greeter = static function (string $name) {
            return "Hello {$name}";
        };
        $countChars = static function (string $string): int {
            return strlen($string);
        };

        self::assertEquals(11, Lambda::of($greeter)->map($countChars)("World"));
    }

    public function testFromCallableWithNumberOfArgsDontWrapMultipleTimes(): void
    {
        $curried = Lambda::of(static function (int $a, int $b): int {
            return $a + $b;
        });

        $plus5 = $curried(5);
        self::assertSame($plus5, Lambda::fromCallableWithNumberOfArgs($plus5, 1));
    }

    public function testFromCallableWithNumberOfArgsThrowsOnInvalidNumberOfArgs(): void
    {
        self::expectException(InvalidStateException::class);
        $curried = Lambda::of(static function (int $a, int $b): int {
            return $a + $b;
        });

        $plus5 = $curried(5);
        self::assertSame($plus5, Lambda::fromCallableWithNumberOfArgs($plus5, 2));
    }
}
