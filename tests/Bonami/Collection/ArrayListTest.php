<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\OutOfBoundsException;
use Bonami\Collection\Monoid\IntSumMonoid;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class ArrayListTest extends TestCase
{
    public function testFromEmpty(): void
    {
        self::assertEquals(new ArrayList([]), ArrayList::fromEmpty());
    }

    public function testFromItems(): void
    {
        self::assertEquals(new ArrayList([1]), ArrayList::of(1));
        self::assertEquals(new ArrayList([1, 2]), ArrayList::of(1, 2));
    }

    public function testFill(): void
    {
        self::assertEquals(new ArrayList(['aa', 'aa', 'aa']), ArrayList::fill('aa', 3));
    }

    public function testRange(): void
    {
        self::assertEquals(new ArrayList([1, 3, 5]), ArrayList::range(1, 5, 2));
    }

    public function testRangeSteppingOverMax(): void
    {
        self::assertEquals(new ArrayList([1, 3, 5]), ArrayList::range(1, 6, 2));
    }

    public function testExplode(): void
    {
        self::assertEquals(new ArrayList(['a', 'b', 'c']), ArrayList::explode(',', 'a,b,c'));
        self::assertEquals(new ArrayList(['a', 'b', 'c']), ArrayList::explode('', 'abc'));
        self::assertEquals(new ArrayList(['š', 'č', 'ř']), ArrayList::explode('', 'ščř'));
    }

    public function testFromIterable(): void
    {
        self::assertEquals(new ArrayList([1, 2, 3]), ArrayList::fromIterable([1, 2, 3]));
        self::assertEquals(new ArrayList([1, 2, 3]), ArrayList::fromIterable(new ArrayList([1, 2, 3])));
        self::assertEquals(new ArrayList([1, 2, 3]), ArrayList::fromIterable(new ArrayIterator([1, 2, 3])));
    }

    public function testCountable(): void
    {
        self::assertCount(2, ArrayList::of(1, 2));
    }

    public function testIsEmpty(): void
    {
        self::assertTrue(ArrayList::fromEmpty()->isEmpty());
        self::assertFalse(ArrayList::of(1, 2)->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        self::assertFalse(ArrayList::fromEmpty()->isNotEmpty());
        self::assertTrue(ArrayList::of(1, 2)->isNotEmpty());
    }

    public function testGet(): void
    {
        $a = ArrayList::of(666);
        self::assertTrue($a->get(0)->equals(Option::some(666)));
        self::assertTrue($a->get(1)->equals(Option::none()));
    }

    public function testGetOrElse(): void
    {
        $a = ArrayList::of(666);
        self::assertSame(666, $a->getOrElse(0, 42));
        self::assertSame(42, $a->getOrElse(1, 42));
    }

    public function testGetUnsafe(): void
    {
        $a = ArrayList::of(666);
        self::assertSame(666, $a->getUnsafe(0));

        $this->expectException(OutOfBoundsException::class);
        $a->getUnsafe(1);
    }

    public function testMap(): void
    {
        $mapped = ArrayList::of(3, 2, 1, 0)->map(static fn ($i, $key) => $i + $key);
        self::assertEquals(ArrayList::of(3, 3, 3, 3), $mapped);
    }

    public function testAp(): void
    {
        /** @var ArrayList<CurriedFunction<string, CurriedFunction<string, string|array<string>>>> */
        $callbacks = ArrayList::fromIterable([
            CurriedFunction::curry2(static fn ($a, $b) => $a . $b),
            CurriedFunction::curry2(static fn ($a, $b) => [$a, $b]),
        ]);
        $mapped = ArrayList::ap(ArrayList::ap($callbacks, ArrayList::of('1', '2')), ArrayList::of('a', 'b'));

        $expected = [
            '1a',
            '1b',
            '2a',
            '2b',
            ['1', 'a'],
            ['1', 'b'],
            ['2', 'a'],
            ['2', 'b'],
        ];

        self::assertEquals($expected, $mapped->toArray());
    }

    public function testApNone(): void
    {
        /** @var ArrayList<CurriedFunction<string, CurriedFunction<string, string|array<string>>>> */
        $callbacks = ArrayList::fromIterable([
            CurriedFunction::curry2(static fn ($a, $b) => $a . $b),
            CurriedFunction::curry2(static fn ($a, $b) => [$a, $b]),
        ]);
        /** @var ArrayList<string> */
        $strings = ArrayList::fromEmpty();
        $mapped = ArrayList::ap(ArrayList::ap($callbacks, ArrayList::of('1', '2')), $strings);

        self::assertEquals([], $mapped->toArray());
    }

    public function testProduct(): void
    {
        $products = ArrayList::product(
            ArrayList::fromIterable([1, 2]),
            ArrayList::fromIterable(['a', 'b']),
        );

        $expected = [
            [1, 'a'],
            [1, 'b'],
            [2, 'a'],
            [2, 'b'],
        ];

        self::assertEquals($expected, $products->toArray());
    }

    public function testProductEmpty(): void
    {
        self::assertEquals(
            [],
            ArrayList::product(ArrayList::of(5), ArrayList::fromEmpty())->toArray(),
        );
    }

    public function testLift(): void
    {
        $lifted = ArrayList::lift(static fn ($a, $b): string => $a . $b);

        $mapped = $lifted(ArrayList::of(1, 2), ArrayList::of('a', 'b'));

        self::assertEquals(['1a', '1b', '2a', '2b'], $mapped->toArray());
    }

    public function testTraverse(): void
    {
        $fillAForOdd = static fn (int $i): ArrayList => $i % 2 === 0
            ? ArrayList::of($i)
            : ArrayList::fromEmpty();

        self::assertEquals(
            ArrayList::of(ArrayList::of(4, 2)),
            ArrayList::traverse([4, 2], $fillAForOdd),
        );
        self::assertEquals(
            ArrayList::fromEmpty(),
            ArrayList::traverse([1, 2], $fillAForOdd),
        );
        self::assertEquals(
            ArrayList::of(ArrayList::fromEmpty()),
            ArrayList::traverse([], $fillAForOdd),
        );
    }

    public function testSequenceWithMultipleValues(): void
    {
        /** @var ArrayList<int|string> $a */
        $a = ArrayList::of(1, 2);
        /** @var ArrayList<int|string> $b */
        $b = ArrayList::of('a', 'b');
        self::assertEquals(
            ArrayList::of(
                ArrayList::fromIterable([1, 'a']),
                ArrayList::fromIterable([1, 'b']),
                ArrayList::fromIterable([2, 'a']),
                ArrayList::fromIterable([2, 'b']),
            ),
            ArrayList::sequence([$a, $b]),
        );
    }

    public function testFlatMap(): void
    {
        self::assertEquals(
            ArrayList::of([0], [1]),
            ArrayList::of(0, 1)->flatMap(static fn (int $i): array => [[$i]]),
        );
    }

    public function testFlatten(): void
    {
        self::assertEquals(
            ArrayList::of([0], [1], [2], [3]),
            ArrayList::of([[0], [1]], [[2], [3]])->flatten(),
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

        self::assertEquals(
            ArrayList::of(2, 6, 10),
            $a->uniqueMap(static fn ($item): int => $item * 2),
        );
    }

    public function testUniqueBy(): void
    {
        $a = new ArrayList([['a' => 1, 'b' => 2], ['a' => 3, 'b' => 4], ['a' => 1, 'b' => 6]]);
        $unique = $a->uniqueBy(static fn ($v) => $v['a']);
        self::assertEquals(2, $unique->count());
        self::assertEquals(6, $unique->head()->getUnsafe()['b']);
        self::assertEquals(4, $unique->last()->getUnsafe()['b']);
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

        self::assertEquals($expected, $objects->uniqueBy(static fn (stdClass $object) => $object->a));
    }

    public function testUnique(): void
    {
        $arrays = new ArrayList([[1, 2], [1, 2, 3], [1, 2]]);
        self::assertEquals(new ArrayList([[1, 2], [1, 2, 3]]), $arrays->unique());
    }

    public function testUnion(): void
    {
        self::assertEquals(
            ArrayList::of(1, 2, 3),
            ArrayList::of(1, 2)->union(ArrayList::of(2, 3)),
        );
    }

    public function testFilter(): void
    {
        self::assertEquals(
            ArrayList::of(1, 3),
            ArrayList::of(1, 2, 3)->filter(static fn ($i): bool => $i % 2 !== 0),
        );
    }

    public function testFind(): void
    {
        self::assertEquals(
            Option::some(2),
            ArrayList::of(1, 2)->find(static fn ($value): bool => $value === 2),
        );
    }

    public function testFindNotMatch(): void
    {
        self::assertEquals(
            Option::none(),
            ArrayList::of(1, 2)->find(static fn ($value): bool => $value === 3),
        );
    }

    public function testFindEmpty(): void
    {
        self::assertEquals(
            Option::none(),
            ArrayList::fromEmpty()->find(tautology()),
        );
    }

    public function testFindKey(): void
    {
        self::assertEquals(
            Option::some(1),
            ArrayList::of(1, 2)->findKey(static fn ($value) => $value === 2),
        );
    }

    public function testFindKeyNotMatch(): void
    {
        self::assertEquals(
            Option::none(),
            ArrayList::of(1, 2)->findKey(static fn ($value) => $value === 3),
        );
    }

    public function testFindKeyEmpty(): void
    {
        self::assertEquals(
            Option::none(),
            ArrayList::fromEmpty()->findKey(tautology()),
        );
    }

    public function testExists(): void
    {
        $integers = ArrayList::of(1, 2, 3);
        self::assertTrue($integers->exists(static fn ($value): bool => $value === 2));
    }

    public function testNotExists(): void
    {
        $integers = ArrayList::of(1, 2, 3);
        self::assertFalse($integers->exists(static fn ($value): bool => $value === 4));
    }

    public function testNotExistsEmpty(): void
    {
        self::assertFalse(ArrayList::fromEmpty()->exists(tautology()));
    }

    public function testContains(): void
    {
        /** @var int|string $i */
        $i = 1;
        self::assertTrue(ArrayList::of($i)->contains('1', false));
        self::assertFalse(ArrayList::of($i)->contains('1', true));
        self::assertTrue(ArrayList::of($i)->contains($i, true));

        $o1 = (object)['a' => $i];
        $o2 = (object)['a' => $i];
        self::assertTrue(ArrayList::of($o1)->contains($o2, false));
        self::assertFalse(ArrayList::of($o1)->contains($o2, true));
        self::assertTrue(ArrayList::of($o1)->contains($o1, true));
    }

    public function testAllMatchCondition(): void
    {
        self::assertTrue(ArrayList::of(true, true)->all(id(...)));
        self::assertFalse(ArrayList::of(true, false)->all(id(...)));
        self::assertTrue(ArrayList::fromEmpty()->all(id(...)));
    }

    public function testSort(): void
    {
        self::assertEquals(
            ArrayList::of('a', 'b', 'c'),
            ArrayList::of('b', 'a', 'c')->sort(comparator()),
        );
    }

    public function testSortDefaultComparator(): void
    {
        self::assertEquals(
            ArrayList::of('a', 'b', 'c'),
            ArrayList::of('b', 'a', 'c')->sort(),
        );
        self::assertEquals(
            ArrayList::of(2, 3, 5),
            ArrayList::of(3, 2, 5)->sort(),
        );
    }

    public function testIndex(): void
    {
        self::assertEquals(
            new Map([
                [10, 1],
                [20, 2],
            ]),
            ArrayList::of(1, 2)->index(static fn ($value) => $value * 10),
        );
    }

    public function testToArray(): void
    {
        self::assertEquals([1, 2], ArrayList::of(1, 2)->toArray());
    }

    public function testReduce(): void
    {
        $list = ArrayList::of(1, 2, 3);
        $sum = $list->reduce(static fn ($reduction, $value) => $reduction + $value, 0);
        self::assertEquals(6, $sum);
    }

    public function testMfold(): void
    {
        $list = ArrayList::of(1, 2, 3);
        $sum = $list->mfold(new IntSumMonoid());
        self::assertEquals(6, $sum);
    }

    public function testSum(): void
    {
        $list = ArrayList::of((object)['a' => 1], (object)['a' => 2], (object)['a' => 3]);
        $sum = $list->sum(static fn (stdClass $o): int => $o->a);
        self::assertEquals(6, $sum);
    }

    public function testMin(): void
    {
        self::assertEquals(Option::some(1), ArrayList::of(3, 1, 2)->min(comparator()));
        self::assertEquals(Option::some(3), ArrayList::of(3, 3, 3)->min(comparator()));
        self::assertEquals(Option::none(), ArrayList::fromEmpty()->min(comparator()));
    }

    public function testMinWithNoCallback(): void
    {
        self::assertEquals(Option::some(1), ArrayList::of(3, 1, 2)->min());
    }

    public function testMax(): void
    {
        self::assertEquals(Option::some(3), ArrayList::of(2, 1, 3)->max(comparator()));
        self::assertEquals(Option::some(3), ArrayList::of(3, 3, 3)->max(comparator()));
        self::assertEquals(Option::none(), ArrayList::fromEmpty()->max(comparator()));
    }

    public function testEach(): void
    {
        $a = ArrayList::of(1, 2);
        $acc = 0;

        $a->each(static function (int $i) use (&$acc): void {
            $acc += $i;
        });
        self::assertEquals(3, $acc);
    }

    public function testTap(): void
    {
        $a = ArrayList::of(1, 2);
        $accumulated = 0;

        $accumulate = static function (int $i) use (&$accumulated): void {
            $accumulated += $i;
        };

        self::assertSame($a, $a->tap($accumulate));
        self::assertSame(3, $accumulated);
    }

    public function testMaxWithNoCallback(): void
    {
        self::assertEquals(Option::some(3), ArrayList::of(2, 1, 3)->max());
    }

    public function testHead(): void
    {
        $integers = ArrayList::of(6, 7, 8);
        self::assertEquals(6, $integers->head()->getUnsafe());
    }

    public function testHeadEmpty(): void
    {
        self::assertSame(Option::none(), ArrayList::fromEmpty()->head());
    }

    public function testTake(): void
    {
        $integers = ArrayList::of(6, 7, 8);
        self::assertEquals(ArrayList::of(6, 7), $integers->take(2));
    }

    public function testTakeFewerElements(): void
    {
        $integers = ArrayList::of(6);
        self::assertEquals(ArrayList::of(6), $integers->take(2));
    }

    public function testSlice(): void
    {
        self::assertEquals(ArrayList::of(1), ArrayList::fill(1, 2)->slice(0, 1));
        self::assertEquals(ArrayList::fromEmpty(), ArrayList::fromEmpty()->slice(0, 1));
    }

    public function testLast(): void
    {
        self::assertSame(Option::none(), ArrayList::fromEmpty()->last());
        self::assertEquals(2, ArrayList::of(1, 2)->last()->getUnsafe());
    }

    public function testWithoutNulls(): void
    {
        self::assertEquals(
            ArrayList::of(1),
            ArrayList::fromIterable([null, 1, null])->withoutNulls(),
        );
    }

    public function testMinus(): void
    {
        $list1 = ArrayList::of(1, 2, 3, 4);
        $list2 = ArrayList::of(3, 4, 5);
        self::assertEquals(ArrayList::of(1, 2), $list1->minus($list2));
        self::assertEquals(ArrayList::of(5), $list2->minus($list1));
    }

    public function testMinusSameReferences(): void
    {
        $a = (object)['foo' => 'a'];
        $b = (object)['foo' => 'b'];
        $c = (object)['foo' => 'c'];
        $d = (object)['foo' => 'd'];
        $list1 = ArrayList::of($a, $b, $c);
        $list2 = ArrayList::of($c, $d);
        self::assertEquals(ArrayList::of($a, $b), $list1->minus([$c, $d]));
        self::assertEquals(ArrayList::of($d), $list2->minus([$b, $c]));
    }

    public function testMinusDifferentReferencesStrict(): void
    {
        $list1 = ArrayList::of((object)['foo' => 'a'], (object)['foo' => 'b']);
        self::assertEquals(
            ArrayList::of((object)['foo' => 'a'], (object)['foo' => 'b']),
            $list1->minus([(object)['foo' => 'b']]),
        );
    }

    public function testMinusDifferentReferencesNonStrict(): void
    {
        $list1 = ArrayList::of((object)['foo' => 'a'], (object)['foo' => 'b']);
        self::assertEquals(
            ArrayList::of((object)['foo' => 'a']),
            $list1->minus([(object)['foo' => 'b']], false),
        );
    }

    public function testMinusOne(): void
    {
        $a = ArrayList::of(1, 2);
        self::assertEquals(ArrayList::of(1), $a->minusOne(2));
        self::assertEquals(ArrayList::of(1, 2), $a->minusOne(3));
    }

    public function testConcat(): void
    {
        $list1 = ArrayList::of(1, 2);
        $list2 = ArrayList::of(2, 3);
        self::assertEquals(ArrayList::of(1, 2, 2, 3), $list1->concat($list2));
        self::assertEquals(ArrayList::of(2, 3, 1, 2), $list2->concat($list1));
    }

    public function testIntersect(): void
    {
        $list1 = ArrayList::of(1, 2);
        $list2 = ArrayList::of(2, 3);
        self::assertEquals(ArrayList::of(2), $list1->intersect($list2));
    }

    public function testIntersectObjects(): void
    {
        $o1 = (object)['a' => 1, 'b' => 1];
        $o2 = (object)['a' => 2, 'b' => 1];
        $o3 = (object)['a' => 3, 'b' => 2];

        $list1 = ArrayList::of($o1, $o2);
        $list2 = ArrayList::of($o2, $o3);
        self::assertEquals(ArrayList::of($o2), $list1->intersect($list2));
    }

    public function testGroupBy(): void
    {
        $o1 = (object)['a' => 1, 'b' => 1];
        $o2 = (object)['a' => 2, 'b' => 1];
        $o3 = (object)['a' => 3, 'b' => 2];

        $grouped = ArrayList::of($o1, $o2, $o3)->groupBy(static fn ($o) => $o->b);

        self::assertEquals(Map::fromIterable([
            [1, ArrayList::of($o1, $o2)],
            [2, ArrayList::of($o3)],
        ]), $grouped);
    }

    public function testChunk(): void
    {
        $a = ArrayList::range(1, 10);

        self::assertEquals(ArrayList::of(
            ArrayList::of(1, 2, 3),
            ArrayList::of(4, 5, 6),
            ArrayList::of(7, 8, 9),
            ArrayList::of(10),
        ), $a->chunk(3));
    }

    public function testCombine(): void
    {
        $a = new ArrayList(range(1, 3));
        $b = new ArrayList(range(11, 13));
        self::assertEquals(new Map([[1, 11], [2, 12], [3, 13]]), $a->combine($b));
    }

    public function testZip(): void
    {
        $strings = ArrayList::of('This', 'Is', 'Not', 'Test', 'Of', 'Emergency', 'Broadcast', 'System');
        $integers = ArrayList::range(0, 14, 2);

        self::assertEquals(ArrayList::of(
            [0, 'This'],
            [2, 'Is'],
            [4, 'Not'],
            [6, 'Test'],
            [8, 'Of'],
            [10, 'Emergency'],
            [12, 'Broadcast'],
            [14, 'System'],
        ), $integers->zip($strings));
    }

    public function testZipMap(): void
    {
        $ints = ArrayList::range(0, 2);
        $map = $ints->zipMap(static fn (int $i): string => chr(ord('a') + $i));
        self::assertEquals(Map::fromIterable([[0, 'a'], [1, 'b'], [2, 'c']]), $map);
    }

    public function testJoin(): void
    {
        self::assertEquals('1, 2, 3', ArrayList::of(1, 2, 3)->join(', '));
    }

    public function testJsonSerialize(): void
    {
        $list = new ArrayList(range(0, 10, 2));
        self::assertEquals('[0,2,4,6,8,10]', json_encode($list));
    }

    public function testNestedJsonSerialize(): void
    {
        $nestedList = (new ArrayList(range(0, 2)))->map(static fn () => ArrayList::of(0, 1));
        self::assertEquals('[[0,1],[0,1],[0,1]]', json_encode($nestedList));
    }

    public function testReverse(): void
    {
        self::assertEquals(ArrayList::of(2, 1), ArrayList::of(1, 2)->reverse());
    }

    public function testToMap(): void
    {
        $pairs = ArrayList::of([1, 'a'], [2, 'b']);
        self::assertEquals(Map::fromIterable([[1, 'a'], [2, 'b']]), $pairs->toMap());
    }

    public function testLazy(): void
    {
        $lazyList = ArrayList::of('a')->lazy();
        self::assertInstanceOf(LazyList::class, $lazyList);
        self::assertEquals(['a'], $lazyList->toArray());
    }

    public function testToString(): void
    {
        $a = ArrayList::fromIterable(range(1, 3));

        self::assertEquals('[1, 2, 3]', (string)$a);
    }

    public function testToStringObjects(): void
    {
        $a = ArrayList::fromIterable([
            (object)['a' => 1],
            (object)['b' => 2],
        ]);

        self::assertMatchesRegularExpression('~^\[\(stdClass\) [0-9a-f]+?, \(stdClass\) [0-9a-f]+?\]$~', (string)$a);
    }

    public function testToStringArrays(): void
    {
        $a = ArrayList::fromIterable([
            ['a' => 1],
            ['b' => 2],
            range(1, 3),
        ]);

        self::assertEquals('[[a => 1], [b => 2], [0 => 1, 1 => 2, 2 => 3]]', (string)$a);
    }
}
