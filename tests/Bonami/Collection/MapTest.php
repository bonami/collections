<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\OutOfBoundsException;
use Bonami\Collection\Hash\IHashable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function PHPUnit\Framework\assertEquals;

class MapTest extends TestCase
{
    public function testFromIterable(): void
    {
        /** @phpstan-var iterable<array{0: string, 1: int}> */
        $iterator = new ArrayIterator([
            ['a', 1],
            ['b', 2],
        ]);

        self::assertEquals(new Map([['a', 1], ['b', 2]]), Map::fromIterable($iterator));
    }

    public function testFromIterableMap(): void
    {
        $iterator = new Map([
            ['a', 1],
            ['b', 2],
        ]);

        self::assertEquals(new Map([['a', 1], ['b', 2]]), Map::fromIterable($iterator));
    }

    public function testGetOrElse(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
        ]);
        self::assertEquals('a', $map->getOrElse(1, 'default'));
        self::assertEquals('default', $map->getOrElse(3, 'default'));
    }

    public function testCountable(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
        ]);
        self::assertCount(2, $map);
    }

    public function testIsEmpty(): void
    {
        self::assertEquals(true, Map::fromEmpty()->isEmpty());
        self::assertEquals(false, Map::fromOnly(1, 'a')->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        self::assertEquals(false, Map::fromEmpty()->isNotEmpty());
        self::assertEquals(true, Map::fromOnly(1, 'a')->isNotEmpty());
    }

    public function testMap(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
        ]);
        $mapped = $map->map(static fn ($value, $key) => sprintf('%s:%s', $value, $key));
        self::assertEquals(ArrayList::fromIterable(['a:1', 'b:2']), $mapped);
    }

    public function testFlatMap(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
        ]);
        $mapped = $map->flatMap(
            static fn (string $value, int $key) => ArrayList::fill(sprintf('%s:%s', $value, $key), $key),
        );
        self::assertEquals(ArrayList::fromIterable(['a:1', 'b:2', 'b:2']), $mapped);
    }

    public function testMapKeys(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
        ]);

        $mapped = $map->mapKeys(static fn ($key) => $key + 1);
        self::assertEquals(new Map([
            [2, 'a'],
            [3, 'b'],
        ]), $mapped);
    }

    public function testMapValues(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
        ]);

        $mapped = $map->mapValues(static fn ($value, $key) => str_repeat($value, $key));
        self::assertEquals(new Map([
            [1, 'a'],
            [2, 'bb'],
        ]), $mapped);
    }

    public function testFilter(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
            [3, 'c'],
            [4, 'd'],
        ]);
        $filtered = $map->filter(static fn ($value, $key) => $key % 2 === 0 || $value === 'c');
        self::assertEquals(new Map([
            [2, 'b'],
            [3, 'c'],
            [4, 'd'],
        ]), $filtered);
    }

    public function testTap(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
        ]);

        $accumulated = 0;
        $accumulateKeys = static function (string $value, int $key) use (&$accumulated): void {
            $accumulated += $key;
        };

        $concatenated = '';
        $concatValues = static function (string $value, int $key) use (&$concatenated): void {
            $concatenated .= $value;
        };

        self::assertSame($map, $map->tap($accumulateKeys)->tap($concatValues));
        self::assertSame(3, $accumulated);
        self::assertSame('ab', $concatenated);
    }

    public function testFind(): void
    {
        self::assertSame(Option::none(), Map::fromEmpty()->find(tautology()));
        self::assertEquals(
            'b',
            Map::fromIterable([[1, 'a'], [2, 'b']])
                ->find(static fn ($value, $key): bool => $key === 2 && $value !== 'a')
                ->getUnsafe(),
        );
    }

    public function testFindKey(): void
    {
        self::assertSame(Option::none(), Map::fromEmpty()->findKey(tautology()));
        self::assertEquals(
            2,
            Map::fromIterable([[1, 'a'], [2, 'b']])
                ->findKey(static fn ($key, $value): bool => $key === 2 && $value !== 'a')
                ->getUnsafe(),
        );
    }

    /**
     * @param mixed $key
     * @param string $expectedMessage
     *
     * @dataProvider provideMissingKeys
     */
    public function testItShouldReportMissingKey($key, string $expectedMessage): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessage($expectedMessage);

        Map::fromEmpty()->getUnsafe($key);
    }

    /** @return iterable<string, array{mixed, string}> */
    public function provideMissingKeys(): iterable
    {

        $hashable = new class implements IHashable {
            public function hashCode(): string
            {
                return 'hash';
            }
        };

        yield 'string key' => ['missing', 'Key (missing) does not exist'];
        yield 'hashable key' => [$hashable, 'Key (' . $hashable::class . ' keyhash:hash) does not exist'];
        yield 'stringable key' => [ArrayList::of(42), 'Key ([42]) does not exist'];
        yield 'generic object key' => [new stdClass(), 'Key (stdClass) does not exist'];
    }

    public function testTake(): void
    {
        self::assertEquals(Map::fromEmpty(), Map::fromEmpty()->take(1));
        self::assertEquals(
            Map::fromIterable([['a', 1], ['b', 2]]),
            Map::fromIterable([['a', 1], ['b', 2], ['c', 3]])->take(2),
        );
    }

    public function testWithoutNulls(): void
    {
        $map = new Map([
            [1, 'a'],
            [2, null],
        ]);

        self::assertEquals(new Map([[1, 'a']]), $map->withoutNulls());
    }

    public function testGetByBooleanKey(): void
    {
        $m = Map::fromIterable([[true, 0], [false, 1]]);
        self::assertSame(1, $m->getUnsafe(false));
        self::assertSame(0, $m->getUnsafe(true));
    }

    public function testKeys(): void
    {
        $map = new Map([
            [(object)[1985, 1, 29], 'Isabel Lucas'],
            [(object)[1984, 7, 11], 'Rachael Taylor'],
            [(object)[1985, 1, 30], 'Elaine Cassidy'],
        ]);

        self::assertEquals(
            ArrayList::fromIterable([(object)[1985, 1, 29], (object)[1984, 7, 11], (object)[1985, 1, 30]]),
            $map->keys(),
        );
    }

    public function testValues(): void
    {
        $map = new Map([
            [1, 'Isabel Lucas'],
            [2, 'Rachael Taylor'],
            [3, 'Elaine Cassidy'],
        ]);

        self::assertEquals(
            ArrayList::fromIterable(['Isabel Lucas', 'Rachael Taylor', 'Elaine Cassidy']),
            $map->values(),
        );
    }

    public function testSortKeysScalarKeys(): void
    {
        $map = new Map([[3, 5], [2, 6], [7, 8]]);
        $result = $map->sortKeys(comparator());

        self::assertEquals(new Map([[2, 6], [3, 5], [7, 8]]), $result);
    }

    public function testSortKeysObjectKeys(): void
    {
        $three = $this->createObject(3);
        $two = $this->createObject(2);
        $seven = $this->createObject(7);
        $map = new Map([[$three, 5], [$two, 6], [$seven, 8]]);
        $result = $map->sortKeys(static fn ($key1, $key2) => $key1->prop <=> $key2->prop);

        self::assertEquals(new Map([[$two, 6], [$three, 5], [$seven, 8]]), $result);
    }

    public function testSortValues(): void
    {
        $map = new Map([['a', 8], ['b', 2], ['c', 3]]);
        $result = $map->sortValues();

        self::assertEquals(new Map([['a', 8], ['c', 3], ['b', 2]]), $result);
    }

    public function testWithoutKeys(): void
    {
        $map = new Map([[1, 2], [3, 4], [5, 6]]);
        self::assertEquals(new Map([[1, 2]]), $map->withoutKeys(new ArrayList([3, 5, 6])));
    }

    public function testWithoutKey(): void
    {
        $map = new Map([[1, 2], [3, 4], [5, 6]]);
        self::assertEquals(new Map([[1, 2], [5, 6]]), $map->withoutKey(3));
    }

    public function testWithoutKeysSomeOutOfBounds(): void
    {
        $map = new Map([[1, 2], [3, 4], [5, 6]]);
        self::assertEquals(new Map([[1, 2]]), $map->withoutKeys(new ArrayList([3, 5, 10000])));
    }

    public function testGetByKeyList(): void
    {
        $map = new Map([[1, 2], [3, 4], [5, 6]]);
        self::assertEquals(new Map([[3, 4], [5, 6]]), $map->getByKeys(new ArrayList([3, 5])));
    }

    public function testGetByKeyListSomeOutOfBounds(): void
    {
        $map = new Map([[1, 2], [3, 4], [5, 6]]);
        self::assertEquals(new Map([[3, 4], [5, 6]]), $map->getByKeys(new ArrayList([3, 5, 7])));
    }

    public function testReduce(): void
    {
        $map = Map::fromAssociativeArray([
            1,
            8,
            34,
            3,
            13,
            2,
            21,
            5,
        ]);

        self::assertEquals(87, $map->reduce(static fn (int $total, int $value) => $total + $value, 0));

        self::assertEquals(115, $map->reduce(
            static fn (int $total, int $value, int $key) => $total + $value + $key,
            0,
        ));

        self::assertEquals(28, $map->reduce(static fn (int $total, int $value, int $key) => $total + $key, 0));
    }

    public function testFilterKeys(): void
    {
        $map = new Map([[1, 2], [3, 4], [5, 6]]);
        $filtered = $map->filterKeys(static fn ($key) => $key > 2);
        self::assertEquals(new Map([[3, 4], [5, 6]]), $filtered);
    }

    public function testGetItems(): void
    {
        $array = [[4, 6], [5, 6], [12, 12]];
        $m = new Map($array);
        self::assertEquals($array, $m->getItems());
    }

    public function testAssociativeArrayScalars(): void
    {
        $ints = [1 => 'a', 2 => 'b'];
        self::assertSame($ints, Map::fromAssociativeArray($ints)->toAssociativeArray());

        $strings = ['A' => 'a', 'B' => 'b'];
        self::assertSame($strings, Map::fromAssociativeArray($strings)->toAssociativeArray());
    }

    public function testAssociativeArrayObjectKeys(): void
    {
        $a = ArrayList::of('a');
        $b = ArrayList::of('b');

        $map = new Map([
            [$a, 1],
            [$b, 2],
            [$a, 3],
        ]);
        self::assertEquals(['[b]' => 2, '[a]' => 3], $map->toAssociativeArray());
    }

    public function testFlattenValues(): void
    {
        $m = Map::fromIterable([
            [1, ['a', 'b']],
            [2, ['c', 'd']],
        ]);
        self::assertEquals(ArrayList::fromIterable(['a', 'b', 'c', 'd']), $m->values()->flatten());
    }

    public function testConcat(): void
    {
        $a1 = [[1, 4], [3, 6], [12, 13]];
        $m1 = new Map($a1);
        $a2 = [[4, 6], [5, 6], [12, 12], [5, 7]];
        $m2 = new Map($a2);
        $merged = $m1->concat($m2);

        self::assertEquals([[1, 4], [3, 6], [12, 13]], $m1->getItems(), 'Method should be immutable');
        self::assertEquals([[4, 6], [5, 7], [12, 12]], $m2->getItems(), 'Method should be immutable');
        self::assertEquals(5, $merged->count());
        self::assertEquals(4, $merged->getUnsafe(1));
        self::assertEquals(6, $merged->getUnsafe(3));
        self::assertEquals(7, $merged->getUnsafe(5), 'Method should accept later value of input pairs');
        self::assertEquals(6, $merged->getUnsafe(4));
        self::assertEquals(12, $merged->getUnsafe(12), 'Method should accept later value of input pairs');
        self::assertEquals([[1, 4], [3, 6], [12, 12], [4, 6], [5, 7]], $merged->getItems());
        try {
            $merged->getUnsafe(7);
            self::fail();
        } catch (InvalidArgumentException $e) {
            assertEquals('Key (7) does not exist', $e->getMessage());
        }
    }

    public function testMinus(): void
    {
        $objects = Map::fromIterable(LazyList::range(1, 14)->zipMap(static fn ($i) => (object)['prop' => $i]));

        $oneToTen = $objects->getByKeys(range(1, 10));
        $fiveToFourteen = $objects->getByKeys(range(5, 14));

        $oneToFour = $objects->getByKeys(range(1, 4));
        $elevenToFourteen = $objects->getByKeys(range(11, 14));

        self::assertEquals(
            $oneToFour->getItems(),
            $oneToTen->minus($fiveToFourteen)->getItems(),
        ); // 1-10 minus 5-14 = 1-4
        self::assertEquals(
            $elevenToFourteen->getItems(),
            $fiveToFourteen->minus($oneToTen)->getItems(),
        ); // 5-14 minus 1-10 = 11-14
    }

    public function testPairs(): void
    {
        $map = new Map([
            ['a', 1],
            ['b', 2],
            ['c', 3],
        ]);
        self::assertEquals(
            ArrayList::fromIterable([
                ['a', 1],
                ['b', 2],
                ['c', 3],
            ]),
            $map->pairs(),
        );
    }

    /**
     * @dataProvider providerChunks
     *
     * @phpstan-param Map<int, array<string>> $sourceMap
     * @phpstan-param array<Map<int, array<string>>> $expectList
     *
     * @phpstan-return void
     */
    public function testChunk(Map $sourceMap, array $expectList): void
    {
        self::assertEquals(ArrayList::fromIterable($expectList), $sourceMap->chunk(2));
    }

    /** @phpstan-return array<string, array<string, mixed>> */
    public function providerChunks(): array
    {
        $object12 = $this->createObject(12);
        $object18 = $this->createObject(18);
        $object15 = $this->createObject(15);
        $object61 = $this->createObject(61);

        return [
            'empty' => [
                'sourceMap' => Map::fromEmpty(),
                'expectList' => [],
            ],
            'numeric-keys' => [
                'sourceMap' => Map::fromAssociativeArray([
                    12 => ['run', 'go', 'walk'],
                    18 => ['home', 'land', 'country'],
                    15 => ['meat', 'food', 'eat'],
                    61 => ['sun', 'moon', 'comet'],
                ]),
                'expectList' => [
                    Map::fromAssociativeArray(
                        [
                            12 => ['run', 'go', 'walk'],
                            18 => ['home', 'land', 'country'],
                        ],
                    ),
                    Map::fromAssociativeArray(
                        [
                            15 => ['meat', 'food', 'eat'],
                            61 => ['sun', 'moon', 'comet'],
                        ],
                    ),
                ],
            ],
            'object-keys' => [
                'sourceMap' => new Map([
                    [$object12, ['run', 'go', 'walk']],
                    [$object18, ['home', 'land', 'country']],
                    [$object15, ['meat', 'food', 'eat']],
                    [$object61, ['sun', 'moon', 'comet']],
                ]),
                'expectList' => [
                    new Map([
                        [$object12, ['run', 'go', 'walk']],
                        [$object18, ['home', 'land', 'country']],
                    ]),
                    new Map([
                        [$object15, ['meat', 'food', 'eat']],
                        [$object61, ['sun', 'moon', 'comet']],
                    ]),
                ],
            ],
        ];
    }

    public function testContains(): void
    {
        $e = new stdClass();
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
            [3, 'c'],
            [4, 'd'],
            [5, $e],
            [0, 'f'],
            [false, 'g'],
            [null, 'h'],
            [6, 0],
            [7, false],
            [8, null],
        ]);

        $map = $map->withoutKey(2);

        self::assertTrue($map->contains('a'));
        self::assertFalse($map->contains('b'));
        self::assertTrue($map->contains('c'));
        self::assertTrue($map->contains('d'));
        self::assertTrue($map->contains($e));
        self::assertFalse($map->contains(new stdClass()));
        self::assertTrue($map->contains('g'));
        self::assertTrue($map->contains('h'));
        self::assertTrue($map->contains(0));
        self::assertTrue($map->contains(false));
        self::assertTrue($map->contains(null));
    }

    public function testHas(): void
    {
        $five = new stdClass();
        $map = new Map([
            [1, 'a'],
            [2, 'b'],
            [3, 'c'],
            [4, 'd'],
            [5, ''],
            [$five, 'e'],
            [0, 'f'],
            [false, 'g'],
            [null, 'h'],
            [6, 0],
            [7, false],
            [8, null],
        ]);

        $map = $map->withoutKeys([2, 5]);

        self::assertTrue($map->has(1));
        self::assertFalse($map->has(2));
        self::assertTrue($map->has(3));
        self::assertTrue($map->has(4));
        self::assertTrue($map->has($five));
        self::assertFalse($map->has(new stdClass()));
        self::assertFalse($map->has(5));
        self::assertTrue($map->has(6));
        self::assertTrue($map->has(7));
        self::assertTrue($map->has(8));
        self::assertTrue($map->has(0));
        self::assertTrue($map->has(false));
        self::assertTrue($map->has(null));
    }

    public function testExistsSimple(): void
    {
        $map = new Map([
            [1, 'A'],
            [2, 'B'],
            [3, 'C'],
        ]);
        self::assertTrue($map->exists(static fn ($value) => $value === 'C'));
        self::assertFalse($map->exists(static fn ($value) => $value === 'D'));
    }

    public function testExistsWithKey(): void
    {
        $map = new Map([
            [1, 1],
            [2, 2],
            [3, 3],
            [4, 4],
        ]);
        self::assertTrue($map->exists(static fn ($value, $key) => $value === $key));
        self::assertFalse($map->exists(static fn ($value, $key) => $value !== $key));
    }

    public function testAll(): void
    {
        $keySameAsValue = static fn ($value, $key) => $value === $key;
        self::assertTrue(Map::fromIterable([[1, 1], [2, 2]])->all($keySameAsValue));
        self::assertFalse(Map::fromIterable([[1, 2], [2, 2]])->all($keySameAsValue));
    }

    public function testExistsInEmptyValues(): void
    {
        $map = new Map([
            [1, 'A'],
            [2, null],
            [3, false],
            [4, 0],
            [5, ''],
        ]);
        self::assertTrue($map->exists(static fn ($value) => $value === 'A'));
        self::assertTrue($map->exists(static fn ($value) => $value === null));
        self::assertTrue($map->exists(static fn ($value) => $value === false));
        self::assertTrue($map->exists(static fn ($value) => $value === 0));
        self::assertTrue($map->exists(static fn ($value) => $value === ''));
        self::assertFalse($map->exists(static fn ($value) => $value === 'X'));
    }

    public function testToString(): void
    {
        $m = Map::fromIterable([
            ['a', 1],
            ['b', 2],
        ]);
        self::assertEquals('{a: 1, b: 2}', (string)$m);
    }

    private function createObject(int $val): stdClass
    {
        $o = new stdClass();
        $o->prop = $val;

        return $o;
    }
}
