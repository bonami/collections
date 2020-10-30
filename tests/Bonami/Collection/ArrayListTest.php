<?php

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class ArrayListTest extends TestCase
{

    public function testFromEmpty(): void
    {
        $this->assertEquals(new ArrayList([]), ArrayList::fromEmpty());
    }

    public function testFromItems(): void
    {
        $this->assertEquals(new ArrayList([1]), ArrayList::of(1));
        $this->assertEquals(new ArrayList([1, 2]), ArrayList::of(1, 2));
    }

    public function testFill(): void
    {
        $this->assertEquals(new ArrayList(['aa', 'aa', 'aa']), ArrayList::fill('aa', 3));
    }

    public function testRange(): void
    {
        $this->assertEquals(new ArrayList([1, 3, 5]), ArrayList::range(1, 5, 2));
    }

    public function testRangeSteppingOverMax(): void
    {
        $this->assertEquals(new ArrayList([1, 3, 5]), ArrayList::range(1, 6, 2));
    }

    public function testExplode(): void
    {
        $this->assertEquals(new ArrayList(['a', 'b', 'c']), ArrayList::explode(',', 'a,b,c'));
        $this->assertEquals(new ArrayList(['a', 'b', 'c']), ArrayList::explode('', 'abc'));
        $this->assertEquals(new ArrayList(['š', 'č', 'ř']), ArrayList::explode('', 'ščř'));
    }

    public function testFromIterable(): void
    {
        $this->assertEquals(new ArrayList([1, 2, 3]), ArrayList::fromIterable([1, 2, 3]));
        $this->assertEquals(new ArrayList([1, 2, 3]), ArrayList::fromIterable(new ArrayList([1, 2, 3])));
        $this->assertEquals(new ArrayList([1, 2, 3]), ArrayList::fromIterable(new ArrayIterator([1, 2, 3])));
    }

    public function testIsIterable(): void
    {
        $this->assertIsIterable(ArrayList::of(1, 2));
    }

    public function testCountable(): void
    {
        $this->assertCount(2, ArrayList::of(1, 2));
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue(ArrayList::fromEmpty()->isEmpty());
        $this->assertFalse(ArrayList::of(1, 2)->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        $this->assertFalse(ArrayList::fromEmpty()->isNotEmpty());
        $this->assertTrue(ArrayList::of(1, 2)->isNotEmpty());
    }

    public function testGet(): void
    {
        $a = ArrayList::of(666);
        $this->assertTrue($a->get(0)->equals(Option::some(666)));
        $this->assertTrue($a->get(1)->equals(Option::none()));
    }

    public function testGetOrElse(): void
    {
        $a = ArrayList::of(666);
        $this->assertSame(666, $a->getOrElse(0, 42));
        $this->assertSame(42, $a->getOrElse(1, 42));
    }

    public function testGetUnsafe(): void
    {
        $a = ArrayList::of(666);
        $this->assertSame(666, $a->getUnsafe(0));

        $this->expectException(OutOfBoundsException::class);
        $a->getUnsafe(1);
    }

    public function testMap(): void
    {
        $mapped = ArrayList::of(3, 2, 1, 0)->map(static function ($i, $key) {
            return $i + $key;
        });
        $this->assertEquals(ArrayList::of(3, 3, 3, 3), $mapped);
    }

    public function testAp(): void
    {
        $callbacks = ArrayList::of(
            function ($a, $b) {
                return $a . $b;
            },
            function ($a, $b) {
                return [$a, $b];
            }
        );
        $mapped = $callbacks
            ->ap(ArrayList::of(1, 2))
            ->ap(ArrayList::of('a', 'b'));

        $expected = [
            '1a',
            [1, 'a'],
            '2a',
            [2, 'a'],
            '1b',
            [1, 'b'],
            '2b',
            [2, 'b'],
        ];

        $this->assertEquals($expected, $mapped->toArray());
    }

    public function testApNone(): void
    {
        $callbacks = ArrayList::of(
            function ($a, $b) {
                return $a . $b;
            },
            function ($a, $b) {
                return [$a, $b];
            }
        );
        $mapped = $callbacks
            ->ap(ArrayList::of(1, 2))
            ->ap(ArrayList::fromEmpty());

        $this->assertEquals([], $mapped->toArray());
    }

    public function testLift(): void
    {
        $lifted = ArrayList::lift(function ($a, $b): string {
            return $a . $b;
        });

        $mapped = $lifted(ArrayList::of(1, 2), ArrayList::of('a', 'b'));

        $this->assertEquals(['1a', '2a', '1b', '2b'], $mapped->toArray());
    }

    public function testTraverse(): void
    {
        /** @phpstan-var callable(int): ArrayList<int> $fillAForOdd */
        $fillAForOdd = function (int $i): ArrayList {
            return $i % 2 === 0 ? ArrayList::of($i) : ArrayList::fromEmpty();
        };

        $this->assertEquals(
            ArrayList::of(ArrayList::of(4, 2)),
            ArrayList::traverse([4, 2], $fillAForOdd)
        );
        $this->assertEquals(
            ArrayList::fromEmpty(),
            ArrayList::traverse([1, 2], $fillAForOdd)
        );
        $this->assertEquals(
            ArrayList::of(ArrayList::fromEmpty()),
            ArrayList::traverse([], $fillAForOdd)
        );
    }

    public function testSequenceWithMultipleValues(): void
    {
        $this->assertEquals(
            ArrayList::of(
                ArrayList::of(1, 'a'),
                ArrayList::of(2, 'a'),
                ArrayList::of(1, 'b'),
                ArrayList::of(2, 'b')
            ),
            ArrayList::sequence([ArrayList::of(1, 2), ArrayList::of('a', 'b')])
        );
    }

    public function testFlatMap(): void
    {
        $this->assertEquals(
            ArrayList::of([0], [1]),
            ArrayList::of(0, 1)->flatMap(static function (int $i): array {
                return [[$i]];
            })
        );
    }

    public function testFlatten(): void
    {
        $this->assertEquals(
            ArrayList::of([0], [1], [2], [3]),
            ArrayList::of([[0], [1]], [[2], [3]])->flatten()
        );
    }

    public function testItShouldFailWhenItemCannotBeFlattened(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Some item cannot be flattened because it is not iterable');

        ArrayList::fromIterable([1, [2]])->flatten();
    }

    public function testUniqueMap(): void
    {
        $a = ArrayList::of(1, 3, 1, 5);

        $this->assertEquals(
            ArrayList::of(2, 6, 10),
            $a->uniqueMap(static function ($item): int {
                return $item * 2;
            })
        );
    }

    public function testUniqueBy(): void
    {
        $a = new ArrayList([['a' => 1, 'b' => 2], ['a' => 3, 'c' => 4], ['a' => 1, 'c' => 6]]);
        $unique = $a->uniqueBy(static function ($v) {
            return $v['a'];
        });
        $this->assertEquals(2, $unique->count());
        $this->assertEquals(6, $unique->head()->getUnsafe()['c']);
        $this->assertEquals(4, $unique->last()->getUnsafe()['c']);
    }

    public function testUniqueByObjects(): void
    {
        $one = (object)['foo' => 1];
        $two = (object)['foo' => 2];
        $objects = new ArrayList([
            (object)['a' => $one, 'b' => $one],
            (object)['a' => $two, 'b' => $one],
            (object)['a' => $one, 'b' => $two],
        ]);
        $expected = new ArrayList([
            (object)['a' => $one, 'b' => $two],
            (object)['a' => $two, 'b' => $one],
        ]);

        $this->assertEquals($expected, $objects->uniqueBy(static function (stdClass $object) {
            return $object->a;
        }));
    }


    public function testUnique(): void
    {
        $arrays = new ArrayList([[1, 2], [1, 2, 3], [1, 2]]);
        $this->assertEquals(new ArrayList([[1, 2], [1, 2, 3]]), $arrays->unique());
    }

    public function testUnion(): void
    {
        $this->assertEquals(
            ArrayList::of(1, 2, 3),
            ArrayList::of(1, 2)->union(ArrayList::of(2, 3))
        );
    }

    public function testFilter(): void
    {
        $this->assertEquals(
            ArrayList::of(1, 3),
            ArrayList::of(1, 2, 3)->filter(static function ($i): bool {
                return $i % 2 !== 0;
            })
        );
    }

    public function testFind(): void
    {
        $this->assertEquals(
            Option::some(2),
            ArrayList::of(1, 2)->find(static function ($value): bool {
                return $value === 2;
            })
        );
    }

    public function testFindNotMatch(): void
    {
        $this->assertEquals(
            Option::none(),
            ArrayList::of(1, 2)->find(static function ($value): bool {
                return $value === 3;
            })
        );
    }

    public function testFindEmpty(): void
    {
        $this->assertEquals(
            Option::none(),
            ArrayList::fromEmpty()->find(tautology())
        );
    }

    public function testFindKey(): void
    {
        $this->assertEquals(
            Option::some(1),
            ArrayList::of(1, 2)->findKey(static function ($value) {
                return $value === 2;
            })
        );
    }

    public function testFindKeyNotMatch(): void
    {
        $this->assertEquals(
            Option::none(),
            ArrayList::of(1, 2)->findKey(static function ($value) {
                return $value === 3;
            })
        );
    }

    public function testFindKeyEmpty(): void
    {
        $this->assertEquals(
            Option::none(),
            ArrayList::of()->findKey(tautology())
        );
    }

    public function testExists(): void
    {
        $integers = ArrayList::of(1, 2, 3);
        $this->assertTrue($integers->exists(static function ($value): bool {
            return $value === 2;
        }));
    }

    public function testNotExists(): void
    {
        $integers = ArrayList::of(1, 2, 3);
        $this->assertFalse($integers->exists(static function ($value): bool {
            return $value === 4;
        }));
    }

    public function testNotExistsEmpty(): void
    {
        $this->assertFalse(ArrayList::fromEmpty()->exists(tautology()));
    }

    public function testContains(): void
    {
        $this->assertTrue(ArrayList::of(1)->contains('1', false));
        $this->assertFalse(ArrayList::of(1)->contains('1', true));
        $this->assertTrue(ArrayList::of(1)->contains(1, true));

        $o1 = (object)['a' => 1];
        $o2 = (object)['a' => 1];
        $this->assertTrue(ArrayList::of($o1)->contains($o2, false));
        $this->assertFalse(ArrayList::of($o1)->contains($o2, true));
        $this->assertTrue(ArrayList::of($o1)->contains($o1, true));
    }

    public function testAllMatchCondition(): void
    {
        $this->assertTrue(ArrayList::of(true, true)->all(identity()));
        $this->assertFalse(ArrayList::of(true, false)->all(identity()));
        $this->assertTrue(ArrayList::fromEmpty()->all(identity()));
    }

    public function testSort(): void
    {
        $this->assertEquals(
            ArrayList::of('a', 'b', 'c'),
            ArrayList::of('b', 'a', 'c')->sort(comparator())
        );
    }

    public function testSortDefaultComparator(): void
    {
        $this->assertEquals(
            ArrayList::of('a', 'b', 'c'),
            ArrayList::of('b', 'a', 'c')->sort()
        );
        $this->assertEquals(
            ArrayList::of(2, 3, 5),
            ArrayList::of(3, 2, 5)->sort()
        );
    }

    public function testIndex(): void
    {
        $this->assertEquals(
            new Map([
                [10, 1],
                [20, 2],
            ]),
            ArrayList::of(1, 2)->index(static function ($value) {
                return $value * 10;
            })
        );
    }

    public function testToArray(): void
    {
        $this->assertEquals([1, 2], ArrayList::of(1, 2)->toArray());
    }

    public function testReduce(): void
    {
        $list = ArrayList::of(1, 2, 3);
        $sum = $list->reduce(static function ($reduction, $value) {
            return $reduction + $value;
        }, 0);
        $this->assertEquals(6, $sum);
    }

    public function testMin(): void
    {
        $this->assertEquals(Option::some(1), ArrayList::of(3, 1, 2)->min(comparator()));
        $this->assertEquals(Option::some(3), ArrayList::of(3, 3, 3)->min(comparator()));
        $this->assertEquals(Option::none(), ArrayList::fromEmpty()->min(comparator()));
    }

    public function testMinWithNoCallback(): void
    {
        $this->assertEquals(Option::some(1), ArrayList::of(3, 1, 2)->min());
    }

    public function testMax(): void
    {
        $this->assertEquals(Option::some(3), ArrayList::of(2, 1, 3)->max(comparator()));
        $this->assertEquals(Option::some(3), ArrayList::of(3, 3, 3)->max(comparator()));
        $this->assertEquals(Option::none(), ArrayList::of()->max(comparator()));
    }

    public function testEach(): void
    {
        $a = ArrayList::of(1, 2);
        $acc = 0;

        $a->each(function (int $i) use (&$acc): void {
            $acc += $i;
        });
        $this->assertEquals(3, $acc);
    }

    public function testMaxWithNoCallback(): void
    {
        $this->assertEquals(Option::some(3), ArrayList::of(2, 1, 3)->max());
    }

    public function testHead(): void
    {
        $integers = ArrayList::of(6, 7, 8);
        $this->assertEquals(6, $integers->head()->getUnsafe());
    }

    public function testHeadEmpty(): void
    {
        $this->assertSame(Option::none(), ArrayList::fromEmpty()->head());
    }

    public function testTake(): void
    {
        $integers = ArrayList::of(6, 7, 8);
        $this->assertEquals(ArrayList::of(6, 7), $integers->take(2));
    }

    public function testTakeFewerElements(): void
    {
        $integers = ArrayList::of(6);
        $this->assertEquals(ArrayList::of(6), $integers->take(2));
    }

    public function testSlice(): void
    {
        $this->assertEquals(ArrayList::of(1), ArrayList::fill(1, 2)->slice(0, 1));
        $this->assertEquals(ArrayList::fromEmpty(), ArrayList::fromEmpty()->slice(0, 1));
    }

    public function testLast(): void
    {
        $this->assertSame(Option::none(), ArrayList::fromEmpty()->last());
        $this->assertEquals(2, ArrayList::of(1, 2)->last()->getUnsafe());
    }

    public function testWithoutNulls(): void
    {
        $this->assertEquals(
            ArrayList::of(1),
            ArrayList::of(null, 1, null)->withoutNulls()
        );
    }

    public function testMinus(): void
    {
        $list1 = ArrayList::of(1, 2, 3, 4);
        $list2 = ArrayList::of(3, 4, 5);
        $this->assertEquals(ArrayList::of(1, 2), $list1->minus($list2));
        $this->assertEquals(ArrayList::of(5), $list2->minus($list1));
    }

    public function testMinusSameReferences(): void
    {
        $a = (object)['foo' => 'a'];
        $b = (object)['foo' => 'b'];
        $c = (object)['foo' => 'c'];
        $d = (object)['foo' => 'd'];
        $list1 = ArrayList::of($a, $b, $c);
        $list2 = ArrayList::of($c, $d);
        $this->assertEquals(ArrayList::of($a, $b), $list1->minus([$c, $d]));
        $this->assertEquals(ArrayList::of($d), $list2->minus([$b, $c]));
    }

    public function testMinusDifferentReferencesStrict(): void
    {
        $list1 = ArrayList::of((object)['foo' => 'a'], (object)['foo' => 'b']);
        $this->assertEquals(
            ArrayList::of((object)['foo' => 'a'], (object)['foo' => 'b']),
            $list1->minus([(object)['foo' => 'b']])
        );
    }

    public function testMinusDifferentReferencesNonStrict(): void
    {
        $list1 = ArrayList::of((object)['foo' => 'a'], (object)['foo' => 'b']);
        $this->assertEquals(
            ArrayList::of((object)['foo' => 'a']),
            $list1->minus([(object)['foo' => 'b']], false)
        );
    }

    public function testMinusOne(): void
    {
        $a = ArrayList::of(1, 2);
        $this->assertEquals(ArrayList::of(1), $a->minusOne(2));
        $this->assertEquals(ArrayList::of(1, 2), $a->minusOne(3));
    }

    public function testConcat(): void
    {
        $list1 = ArrayList::of(1, 2);
        $list2 = ArrayList::of(2, 3);
        $this->assertEquals(ArrayList::of(1, 2, 2, 3), $list1->concat($list2));
        $this->assertEquals(ArrayList::of(2, 3, 1, 2), $list2->concat($list1));
    }

    public function testIntersect(): void
    {
        $list1 = ArrayList::of(1, 2);
        $list2 = ArrayList::of(2, 3);
        $this->assertEquals(ArrayList::of(2), $list1->intersect($list2));
    }

    public function testIntersectObjects(): void
    {
        $o1 = (object)['a' => 1, 'b' => 1];
        $o2 = (object)['a' => 2, 'b' => 1];
        $o3 = (object)['a' => 3, 'b' => 2];

        $list1 = ArrayList::of($o1, $o2);
        $list2 = ArrayList::of($o2, $o3);
        $this->assertEquals(ArrayList::of($o2), $list1->intersect($list2));
    }

    public function testGroupBy(): void
    {
        $o1 = (object)['a' => 1, 'b' => 1];
        $o2 = (object)['a' => 2, 'b' => 1];
        $o3 = (object)['a' => 3, 'b' => 2];

        $grouped = ArrayList::of($o1, $o2, $o3)->groupBy(static function ($o) {
            return $o->b;
        });

        $this->assertEquals(Map::fromIterable([
            [1, ArrayList::of($o1, $o2)],
            [2, ArrayList::of($o3)],
        ]), $grouped);
    }

    public function testChunk(): void
    {
        $a = ArrayList::range(1, 10);

        $this->assertEquals(ArrayList::of(
            ArrayList::of(1, 2, 3),
            ArrayList::of(4, 5, 6),
            ArrayList::of(7, 8, 9),
            ArrayList::of(10)
        ), $a->chunk(3));
    }

    public function testCombine(): void
    {
        $a = new ArrayList(range(1, 3));
        $b = new ArrayList(range(11, 13));
        $this->assertEquals(new Map([[1, 11], [2, 12], [3, 13]]), $a->combine($b));
    }

    public function testZip(): void
    {
        $strings = ArrayList::of('This', 'Is', 'Not', 'Test', 'Of', 'Emergency', 'Broadcast', 'System');
        $integers = ArrayList::range(0, 14, 2);

        $this->assertEquals(ArrayList::of(
            [0, 'This'],
            [2, 'Is'],
            [4, 'Not'],
            [6, 'Test'],
            [8, 'Of'],
            [10, 'Emergency'],
            [12, 'Broadcast'],
            [14, 'System']
        ), $integers->zip($strings));
    }

    public function testZipMap(): void
    {
        $ints = ArrayList::range(0, 2);
        $map = $ints->zipMap(function (int $i): string {
            return chr(ord('a') + $i);
        });
        $this->assertEquals(Map::fromIterable([[0, 'a'], [1, 'b'], [2, 'c']]), $map);
    }

    public function testJoin(): void
    {
        $this->assertEquals('1, 2, 3', ArrayList::of(1, 2, 3)->join(', '));
    }

    public function testJsonSerialize(): void
    {
        $list = new ArrayList(range(0, 10, 2));
        $this->assertEquals('[0,2,4,6,8,10]', json_encode($list));
    }

    public function testNestedJsonSerialize(): void
    {
        $nestedList = (new ArrayList(range(0, 2)))->map(static function () {
            return ArrayList::of(0, 1);
        });
        $this->assertEquals('[[0,1],[0,1],[0,1]]', json_encode($nestedList));
    }

    public function testReverse(): void
    {
        $this->assertEquals(ArrayList::of(2, 1), ArrayList::of(1, 2)->reverse());
    }

    public function testToMap(): void
    {
        $pairs = ArrayList::of([1, 'a'], [2, 'b']);
        $this->assertEquals(Map::fromIterable([[1, 'a'], [2, 'b']]), $pairs->toMap());
    }

    public function testToString(): void
    {
        $a = ArrayList::fromIterable(range(1, 3));

        $this->assertEquals('[1, 2, 3]', (string) $a);
    }

    public function testToStringObjects(): void
    {
        $a = ArrayList::fromIterable([
            (object) ['a' => 1],
            (object) ['b' => 2],
        ]);

        $this->assertRegExp('~^\[\(stdClass\) [0-9a-f]+?, \(stdClass\) [0-9a-f]+?\]$~', (string) $a);
    }

    public function testToStringArrays(): void
    {
        $a = ArrayList::fromIterable([
            ['a' => 1],
            ['b' => 2],
            range(1, 3),
        ]);

        $this->assertEquals('[[a => 1], [b => 2], [0 => 1, 1 => 2, 2 => 3]]', (string) $a);
    }
}
