<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\InvalidEnumValueException;
use PHPUnit\Framework\TestCase;
use stdClass;

class EnumTest extends TestCase
{

    public function testItShouldThrowExceptionWhenCreateMethodArgumentIsObject(): void
    {
        $this->expectException(InvalidEnumValueException::class);
        $this->expectExceptionMessage('Invalid value "stdClass", one of scalar A, B, C expected');

        TestEnum::create(new stdClass());
    }

    public function testItShouldCreateInstanceByString(): void
    {
        self::assertEquals(TestEnum::create(TestEnum::A), TestEnum::create('A'));
    }

    public function testItShouldFailWhenValueOutOfDefinedValues(): void
    {
        $this->expectException(InvalidEnumValueException::class);
        $this->expectExceptionMessage('Invalid string value "D", one of A, B, C expected');

        TestEnum::create('D');
    }

    public function testItShouldGetNameOfConstantFromInstance(): void
    {
        self::assertEquals('A', TestEnum::create(TestEnum::A)->getConstName());
    }

    public function testExists(): void
    {
        self::assertTrue(TestEnum::exists('A'));
        self::assertFalse(TestEnum::exists('D'));
    }

    public function testGetListComplement(): void
    {
        self::assertEquals(
            EnumList::fromIterable([TestEnum::create(TestEnum::B), TestEnum::create(TestEnum::C)]),
            TestEnum::getListComplement(TestEnum::create(TestEnum::A))
        );
        self::assertEquals(
            EnumList::fromIterable([TestEnum::create(TestEnum::B)]),
            TestEnum::getListComplement(TestEnum::create(TestEnum::A), TestEnum::create(TestEnum::C))
        );
    }

    public function testJsonSerializable(): void
    {
        $serialized = json_encode(['foo' => TestEnum::create(TestEnum::A)]);
        assert(is_string($serialized));
        self::assertJson('{"foo": "A"}', $serialized);
    }
}

// @codingStandardsIgnoreStart
class TestEnum extends Enum
{
    public const A = 'A';
    public const B = 'B';
    public const C = 'C';
}
// @codingStandardsIgnoreEnd
