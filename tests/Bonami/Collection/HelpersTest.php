<?php

declare(strict_types=1);

namespace Bonami\Collection;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testCompose(): void
    {
        $multiplyBy3 = static fn (int $x): int => $x * 3;
        $add2 = static fn (int $x): int => $x + 2;

        self::assertEquals(9, compose($multiplyBy3, $add2)(1));
        self::assertEquals(5, compose($add2, $multiplyBy3)(1));
    }
}
