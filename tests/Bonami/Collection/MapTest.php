<?php

namespace Bonami\Collection;

use ArrayIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use stdClass;

class MapTest extends TestCase {

	public function testFromIterable(): void {
		$iterator = new ArrayIterator([
			['a', 1],
			['b', 2],
		]);

		$this->assertEquals(new Map([['a', 1], ['b', 2]]), Map::fromIterable($iterator));
	}

	public function testFromIterableMap(): void {
		$iterator = new Map([
			['a', 1],
			['b', 2],
		]);

		$this->assertEquals(new Map([['a', 1], ['b', 2]]), Map::fromIterable($iterator));
	}

	public function testGetOrElse(): void {
		$map = new Map([
			[1, "a"],
			[2, "b"],
		]);
		$this->assertEquals("a", $map->getOrElse(1, "default"));
		$this->assertEquals("default", $map->getOrElse(3, "default"));
	}

	public function testCountable(): void {
		$map = new Map([
			[1, "a"],
			[2, "b"],
		]);
		$this->assertCount(2, $map);
	}

	public function testIsEmpty(): void {
		$this->assertEquals(true, Map::fromEmpty()->isEmpty());
		$this->assertEquals(false, Map::fromOnly(1, "a")->isEmpty());
	}

	public function testIsNotEmpty(): void {
		$this->assertEquals(false, Map::fromEmpty()->isNotEmpty());
		$this->assertEquals(true, Map::fromOnly(1, "a")->isNotEmpty());
	}

	public function testMap(): void {
		$map = new Map([
			[1, "a"],
			[2, "b"],
		]);
		$mapped = $map->map(function ($value, $key) { return "$value:$key"; });
		$this->assertEquals(ArrayList::fromIterable(["a:1", "b:2"]), $mapped);
	}

	public function testMapKeys(): void {
		$map = new Map([
			[1, "a"],
			[2, "b"],
		]);

		$mapped = $map->mapKeys(function ($key) { return $key + 1; });
		$this->assertEquals(new Map([
			[2, "a"],
			[3, "b"],
		]), $mapped);
	}

	public function testMapValues(): void {
		$map = new Map([
			[1, "a"],
			[2, "b"],
		]);

		$mapped = $map->mapValues(function ($value, $key) { return str_repeat($value, $key); });
		$this->assertEquals(new Map([
			[1, "a"],
			[2, "bb"],
		]), $mapped);
	}

	public function testFilter(): void {
		$map = new Map([
			[1, "a"],
			[2, "b"],
			[3, "c"],
			[4, "d"],
		]);
		$filtered = $map->filter(function ($value, $key) { return $key % 2 === 0 || $value === "c"; });
		$this->assertEquals(new Map([
			[2, "b"],
			[3, "c"],
			[4, "d"],
		]), $filtered);
	}

	public function testFind(): void {
		$this->assertSame(Option::none(), Map::fromEmpty()->find(tautology()));
		$this->assertEquals(
			'b',
			Map::fromIterable([[1, 'a'], [2, 'b']])->find(function ($value, $key): bool {
				return $key === 2 && $value !== 'a';
			})->getUnsafe()
		);
	}

	public function testTake(): void {
		$this->assertEquals(Map::fromEmpty(), Map::fromEmpty()->take(1));
		$this->assertEquals(
			Map::fromIterable([['a', 1], ['b', 2]]),
			Map::fromIterable([['a', 1], ['b', 2], ['c', 3]])->take(2)
		);
	}

	public function testWithoutNulls(): void {
		$map = new Map([
			[1, "a"],
			[2, null],
		]);

		$this->assertEquals(new Map([[1, "a"]]), $map->withoutNulls());
	}

	public function testGetByBooleanKey(): void {
		$m = Map::fromIterable([[true, 0], [false, 1]]);
		$this->assertSame(1, $m->getUnsafe(false));
		$this->assertSame(0, $m->getUnsafe(true));
	}

	public function testKeys() {
		$map = new Map([
			[(object)[1985, 1, 29], "Isabel Lucas"],
			[(object)[1984, 7, 11], "Rachael Taylor"],
			[(object)[1985, 1, 30], "Elaine Cassidy"],
		]);

		$this->assertEquals(
			ArrayList::fromIterable([(object)[1985, 1, 29], (object)[1984, 7, 11], (object)[1985, 1, 30]]),
			$map->keys()
		);
	}

	public function testValues() {
		$map = new Map([
			[1, "Isabel Lucas"],
			[2, "Rachael Taylor"],
			[3, "Elaine Cassidy"],
		]);

		$this->assertEquals(
			ArrayList::fromIterable(["Isabel Lucas", "Rachael Taylor", "Elaine Cassidy"]),
			$map->values()
		);
	}

	public function testSortKeysScalarKeys(): void {
		$map = new Map([[3, 5], [2, 6], [7, 8]]);
		$result = $map->sortKeys(comparator());

		$this->assertEquals(new Map([[2, 6], [3, 5], [7, 8]]), $result);
	}

	public function testSortKeysObjectKeys(): void {
		$three = $this->createObject(3);
		$two = $this->createObject(2);
		$seven = $this->createObject(7);
		$map = new Map([[$three, 5], [$two, 6], [$seven, 8]]);
		$result = $map->sortKeys(function($key1, $key2) {
			return $key1->prop <=> $key2->prop;
		});

		$this->assertEquals(new Map([[$two, 6], [$three, 5], [$seven, 8]]), $result);
	}

	public function testSortValues(): void {
		$map = new Map([['a', 8], ['b', 2], ['c', 3]]);
		$result = $map->sortValues();

		$this->assertEquals(new Map([['a', 8], ['c', 3], ['b', 2]]), $result);
	}

	public function testWithoutKeys(): void {
		$map = new Map([[1, 2], [3, 4], [5, 6]]);
		$this->assertEquals(new Map([[1, 2]]), $map->withoutKeys(new ArrayList([3, 5, 6])));
	}

	public function testWithoutKey(): void {
		$map = new Map([[1, 2], [3, 4], [5, 6]]);
		$this->assertEquals(new Map([[1, 2], [5, 6]]), $map->withoutKey(3));
	}

	public function testWithoutKeysSomeOutOfBounds(): void {
		$map = new Map([[1, 2], [3, 4], [5, 6]]);
		$this->assertEquals(new Map([[1, 2]]), $map->withoutKeys(new ArrayList([3, 5, 10000])));
	}

	public function testGetByKeyList(): void {
		$map = new Map([[1, 2], [3, 4], [5, 6]]);
		$this->assertEquals(new Map([[3, 4], [5, 6]]), $map->getByKeys(new ArrayList([3, 5])));
	}

	public function testGetByKeyListSomeOutOfBounds(): void {
		$map = new Map([[1, 2], [3, 4], [5, 6]]);
		$this->assertEquals(new Map([[3, 4], [5, 6]]), $map->getByKeys(new ArrayList([3, 5, 7])));
	}

	public function testReduce(): void {
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

		$this->assertEquals(87, $map->reduce(function(int $total, int $value) {
			return $total + $value;
		}, 0));

		$this->assertEquals(115, $map->reduce(function(int $total, int $value, int $key) {
			return $total + $value + $key;
		}, 0));

		$this->assertEquals(28, $map->reduce(function(int $total, int $value, int $key) {
			return $total + $key;
		}, 0));
	}

	public function testFilterKeys(): void {
		$map = new Map([[1, 2], [3, 4], [5, 6]]);
		$filtered = $map->filterKeys(function($key) {
			return $key > 2;
		});
		$this->assertEquals(new Map([[3, 4], [5, 6]]), $filtered);
	}

	public function testGetItems(): void {
		$array = [[4, 6], [5, 6], [12, 12]];
		$m = new Map($array);
		$this->assertEquals($array, $m->getItems());
	}

	public function testFlattenValues(): void {
		$m = Map::fromIterable([
			[1, ['a', 'b']],
			[2, ['c', 'd']],
		]);
		$this->assertEquals(ArrayList::fromIterable(['a', 'b', 'c', 'd']), $m->values()->flatten());
	}

	public function testConcat(): void {
		$a1 = [[1, 4], [3, 6], [12, 13]];
		$m1 = new Map($a1);
		$a2 = [[4, 6], [5, 6], [12, 12], [5, 7]];
		$m2 = new Map($a2);
		$merged = $m1->concat($m2);

		$this->assertEquals([[1, 4], [3, 6], [12, 13]], $m1->getItems(), 'Method should be immutable');
		$this->assertEquals([[4, 6], [5, 7], [12, 12]], $m2->getItems(), 'Method should be immutable');
		$this->assertEquals(5, $merged->count());
		$this->assertEquals(4, $merged->getUnsafe(1));
		$this->assertEquals(6, $merged->getUnsafe(3));
		$this->assertEquals(7, $merged->getUnsafe(5), 'Method should accept later value of input pairs');
		$this->assertEquals(6, $merged->getUnsafe(4));
		$this->assertEquals(12, $merged->getUnsafe(12), 'Method should accept later value of input pairs');
		$this->assertEquals([[1, 4], [3, 6], [12, 12], [4, 6], [5, 7]], $merged->getItems());
		try {
			$merged->getUnsafe(7);
			$this->assertTrue(false);
		} catch (InvalidArgumentException $e) {
			$this->assertTrue(true);
		}
	}

	public function testMinus(): void {
		$objects = Map::fromIterable(LazyList::range(1, 14)->map(function($i) {
			return [(object)['prop' => $i], $i];
		}));

		$oneToTen = $objects->getByKeys(range(1, 10));
		$fiveToFourteen = $objects->getByKeys(range(5, 14));

		$oneToFour = $objects->getByKeys(range(1, 4));
		$elevenToFourteen = $objects->getByKeys(range(11, 14));

		$this->assertEquals($oneToFour->getItems(), $oneToTen->minus($fiveToFourteen)->getItems()); // 1-10 minus 5-14 = 1-4
		$this->assertEquals($elevenToFourteen->getItems(), $fiveToFourteen->minus($oneToTen)->getItems()); // 5-14 minus 1-10 = 11-14
	}

	public function testPairs(): void {
		$map = new Map([
			['a', 1],
			['b', 2],
			['c', 3],
		]);
		$this->assertEquals(
			ArrayList::fromIterable([
				['a', 1],
				['b', 2],
				['c', 3],
			]),
			$map->pairs()
		);
	}

	/** @dataProvider providerChunks */
	public function testChunk(Map $sourceMap, array $expectList): void {
		$this->assertEquals(ArrayList::fromIterable($expectList), $sourceMap->chunk(2));
	}

	public function providerChunks(): array {
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
						]
					),
					Map::fromAssociativeArray(
						[
							15 => ['meat', 'food', 'eat'],
							61 => ['sun', 'moon', 'comet'],
						]
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

	public function testGetValueWithRecursiveArrayAsKey(): void {
		$mapDefinition = [];
		for ($i = 0; $i < 100; $i++) {
			$mapDefinition[$i] = [$this->createRecursiveArray(random_int(0, 10), $i), $i];
		}
		$map = new Map($mapDefinition);
		foreach ($mapDefinition as $i => $pairDefinition) {
			$this->assertEquals($pairDefinition[1], $i);
			$this->assertEquals($pairDefinition[1], $map->getUnsafe($pairDefinition[0]));
		}
	}

	public function testContains(): void {
		$e = new stdClass();
		$map = new Map([
			[1, 'a'],
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
		$this->assertTrue($map->contains('a'));
		$this->assertFalse($map->contains('b'));
		$this->assertTrue($map->contains('c'));
		$this->assertTrue($map->contains('d'));
		$this->assertTrue($map->contains($e));
		$this->assertFalse($map->contains(new stdClass()));
		$this->assertTrue($map->contains('g'));
		$this->assertTrue($map->contains('h'));
		$this->assertTrue($map->contains(0));
		$this->assertTrue($map->contains(false));
		$this->assertTrue($map->contains(null));
	}

	public function testHas(): void {
		$five = new stdClass();
		$map = new Map([
			[1, 'a'],
			[3, 'c'],
			[4, 'd'],
			[$five, 'e'],
			[0, 'f'],
			[false, 'g'],
			[null, 'h'],
			[6, 0],
			[7, false],
			[8, null],
		]);
		$this->assertTrue($map->has(1));
		$this->assertFalse($map->has(2));
		$this->assertTrue($map->has(3));
		$this->assertTrue($map->has(4));
		$this->assertTrue($map->has($five));
		$this->assertFalse($map->has(new stdClass()));
		$this->assertFalse($map->has(5));
		$this->assertTrue($map->has(6));
		$this->assertTrue($map->has(7));
		$this->assertTrue($map->has(8));
		$this->assertTrue($map->has(0));
		$this->assertTrue($map->has(false));
		$this->assertTrue($map->has(null));
	}

	public function testExistsSimple(): void {
		$map = new Map([
			[1, 'A'],
			[2, 'B'],
			[3, 'C'],
		]);
		$this->assertTrue($map->exists(function ($value) {
			return $value === 'C';
		}));
		$this->assertFalse($map->exists(function ($value) {
			return $value === 'D';
		}));
	}

	public function testExistsWithKey(): void {
		$map = new Map([
			[1, 1],
			[2, 2],
			[3, 3],
			[4, 4],
		]);
		$this->assertTrue($map->exists(function ($value, $key) {
			return $value === $key;
		}));
		$this->assertFalse($map->exists(function ($value, $key) {
			return $value !== $key;
		}));
	}

	public function testAll(): void {
		$keySameAsValue = function ($value, $key) { return $value === $key; };
		$this->assertTrue(Map::fromIterable([[1, 1], [2, 2]])->all($keySameAsValue));
		$this->assertFalse(Map::fromIterable([[1, 2], [2, 2]])->all($keySameAsValue));
	}

	public function testExistsInEmptyValues(): void {
		$map = new Map([
			[1, 'A'],
			[2, null],
			[3, false],
			[4, 0],
			[5, ''],
		]);
		$this->assertTrue((bool)$map->exists(function ($value) {
			return $value === 'A';
		}));
		$this->assertTrue((bool)$map->exists(function ($value) {
			return $value === null;
		}));
		$this->assertTrue((bool)$map->exists(function ($value) {
			return $value === false;
		}));
		$this->assertTrue((bool)$map->exists(function ($value) {
			return $value === 0;
		}));
		$this->assertTrue((bool)$map->exists(function ($value) {
			return $value === '';
		}));
		$this->assertFalse((bool)$map->exists(function ($value) {
			return $value === 'X';
		}));
	}

	public function testToString(): void {
		$m = Map::fromIterable([
			['a', 1],
			['b', 2],
		]);
		$this->assertEquals('{a: 1, b: 2}', (string) $m);
	}

	private function createRecursiveArray($deepness, $identification): array {
		$array = [
			'someKey' => 'someValue',
			'identification' => $identification,
			'changedIdentification' => $identification * $deepness,
		];
		if ($deepness > 0) {
			$array['subValues'] = $this->createRecursiveArray($deepness - 1, $identification);
		}
		return $array;
	}

	private function createObject($val) {
		$o = new stdClass();
		$o->prop = $val;

		return $o;
	}

}
