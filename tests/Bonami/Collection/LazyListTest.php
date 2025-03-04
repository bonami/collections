<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Monoid\IntSumMonoid;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class LazyListTest extends TestCase
{
    public function testRange(): void
    {
        $range = LazyList::range(3, 30, 3);
        self::assertEquals(range(3, 30, 3), $range->toArray());
    }

    public function testInfinityRange(): void
    {
        $range = LazyList::range(1)
            ->filter(static fn (int $number): bool => $number % 3 === 0)
            ->take(10)
            ->toArray();
        self::assertEquals(range(3, 30, 3), $range);
    }

    public function testFill(): void
    {
        $fill = LazyList::fill('a', 2);
        self::assertEquals(['a', 'a'], $fill->toArray());
    }

    public function testInfinityFill(): void
    {
        $filled = LazyList::fill('a')
            ->take(10)
            ->toArray();
        self::assertEquals(array_fill(0, 10, 'a'), $filled);
    }

    public function testFromIterable(): void
    {
        $lazyList = LazyList::fromIterable(new ArrayIterator([1, 2]));
        self::assertEquals([1, 2], $lazyList->toArray());
    }

    public function testFromItems(): void
    {
        $lazyList = LazyList::of(1, 2);
        self::assertEquals([1, 2], $lazyList->toArray());
    }

    public function testMap(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $mapped = $lazyList->map(static fn ($item) => $item * 10);

        self::assertEquals(range(10, 100, 10), iterator_to_array($mapped));
    }

    public function testMapWithKey(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10, 2)));
        $mapped = $lazyList->map(static fn ($item, $index) => $index);

        self::assertEquals(range(0, 4), iterator_to_array($mapped));
    }

    public function testAp(): void
    {
        /** @var LazyList<CurriedFunction<string, CurriedFunction<string, string|array<string>>>> */
        $callbacks = LazyList::fromIterable([
            CurriedFunction::curry2(static fn ($a, $b) => $a . $b),
            CurriedFunction::curry2(static fn ($a, $b) => [$a, $b]),
        ]);
        $numbersApplied = LazyList::ap($callbacks, LazyList::of('1', '2'));
        $lettersApplied = LazyList::ap($numbersApplied, LazyList::of('a', 'b'));

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

        self::assertEquals($expected, $lettersApplied->toArray());
    }

    public function testApNone(): void
    {
        /** @var LazyList<CurriedFunction<string, CurriedFunction<string, string|array<string>>>> */
        $callbacks = LazyList::fromIterable([
            CurriedFunction::curry2(static fn ($a, $b) => $a . $b),
            CurriedFunction::curry2(static fn ($a, $b) => [$a, $b]),
        ]);
        /** @var LazyList<string> $empty */
        $empty = LazyList::fromEmpty();
        $mapped = LazyList::ap(LazyList::ap($callbacks, LazyList::of('1', '2')), $empty);

        self::assertEquals([], $mapped->toArray());
    }

    public function testLift(): void
    {
        $lifted = LazyList::lift(static fn ($a, $b) => $a . $b);

        $mapped = $lifted(LazyList::of(1, 2), LazyList::of('a', 'b'));

        self::assertEquals(['1a', '1b', '2a', '2b'], $mapped->toArray());
    }

    public function testSequence(): void
    {
        self::assertEquals(
            [ArrayList::of(1, 2)],
            LazyList::sequence([LazyList::of(1), LazyList::of(2)])->toArray(),
        );
        self::assertEquals(
            [],
            LazyList::sequence([LazyList::of(1), LazyList::fromEmpty()])->toArray(),
        );
        /** @phpstan-var array<LazyList<int>> $empty */
        $empty = [];
        self::assertEquals(
            [ArrayList::fromEmpty()],
            LazyList::sequence($empty)->toArray(),
        );
    }

    public function testSequenceWithMultipleValues(): void
    {
        /** @var array<LazyList<int|string>> $iterable */
        $iterable = [LazyList::of(1, 2), LazyList::of('a', 'b')];
        self::assertEquals(
            [
                ArrayList::fromIterable([1, 'a']),
                ArrayList::fromIterable([1, 'b']),
                ArrayList::fromIterable([2, 'a']),
                ArrayList::fromIterable([2, 'b']),
            ],
            LazyList::sequence($iterable)->toArray(),
        );
    }

    public function testFlatMap(): void
    {
        $lazyList = new LazyList([1, 2, 3]);
        $mapped = $lazyList->flatMap(static fn (int $item): array => [$item, [$item * 2]]);
        self::assertEquals([1, [2], 2, [4], 3, [6]], $mapped->toArray());
    }

    public function testFlatten(): void
    {
        $lazyList = new LazyList([[[1], [2]], [[3]]]);
        self::assertEquals([[1], [2], [3]], $lazyList->flatten()->toArray());
    }

    public function testItShouldFailWhenItemCannotBeFlattened(): void
    {
        $lazyList = new LazyList([[[1], [2]], 3]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Some item cannot be flattened because it is not iterable');
        $lazyList->flatten()->toArray();
    }

    public function testEach(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));

        $accumulator = 0;
        $lazyList->each(static function (int $item) use (&$accumulator): void {
            $accumulator += $item;
        });

        self::assertEquals(55, $accumulator);
    }

    public function testTap(): void
    {
        $lazyList = LazyList::range(1, 3);

        $accumulated = 0;
        $acumulate = static function (int $item) use (&$accumulated): void {
            $accumulated += $item;
        };

        $concatenated = '';
        $concat = static function (string $string) use (&$concatenated): void {
            $concatenated .= $string;
        };

        $materialized = $lazyList
            ->tap($acumulate)
            ->map(static fn (int $x): string => (string)$x)
            ->tap($concat)
            ->toArray();

        self::assertSame(['1', '2', '3'], $materialized);
        self::assertSame(6, $accumulated);
        self::assertSame('123', $concatenated);
    }

    public function testReduce(): void
    {
        $lazyList = new LazyList(range(1, 3));

        $sum = $lazyList->reduce(static fn ($sum, $item) => $sum + $item, 0);
        self::assertEquals(6, $sum);
    }

    public function testMfold(): void
    {
        $list = LazyList::of(1, 2, 3);
        $sum = $list->mfold(new IntSumMonoid());
        self::assertEquals(6, $sum);
    }

    public function testSum(): void
    {
        $list = LazyList::of((object)['a' => 1], (object)['a' => 2], (object)['a' => 3]);
        $sum = $list->sum(static fn (stdClass $o): int => $o->a);
        self::assertEquals(6, $sum);
    }

    public function testScan(): void
    {
        $lazyList = LazyList::fill(1);

        $sum = $lazyList->scan(static fn ($sum, $item) => $sum + $item, 0);
        self::assertEquals([1, 2, 3], $sum->take(3)->toArray());
    }

    public function testMapRepeatedCall(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $mapped1 = $lazyList->map(static fn ($item) => $item * 10);
        $mapped2 = $lazyList->map(static fn ($item) => $item * 100);

        self::assertEquals(range(10, 100, 10), iterator_to_array($mapped1));
        self::assertEquals(range(100, 1000, 100), iterator_to_array($mapped2));
    }

    public function testDoWhile(): void
    {
        $taken = [];
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $lazyList
            ->tap(static function (int $i) use (&$taken): void {
                $taken[] = $i;
            })
            ->doWhile(static fn (int $i): bool => $i <= 3);

        self::assertEquals(range(1, 4), $taken);
    }

    public function testRun(): void
    {
        $taken = [];
        $lazyList = LazyList::range(1, 3)
            ->tap(static function (int $i) use (&$taken): void {
                $taken[] = $i;
            });

        self::assertEquals([], $taken);
        $lazyList->run();
        self::assertEquals(range(1, 3), $taken);
    }

    public function testTakeWhile(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $taken = $lazyList->takeWhile(static fn (int $i): bool => $i <= 3);

        self::assertEquals(range(1, 3), iterator_to_array($taken));
    }

    public function testTake(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $taken = $lazyList->take(5);

        self::assertEquals(range(1, 5), iterator_to_array($taken));
    }

    public function testTakeFilteredInfiniteLazyList(): void
    {
        $lazyList = LazyList::range(1);
        $taken = $lazyList->filter(static fn ($x) => $x < 10)->filter(static fn ($x) => $x >= 5)->take(5);

        self::assertEquals(5, iterator_count($taken));
    }

    public function testChunk(): void
    {
        $lazyList = LazyList::range(1, 10);
        $chunked = $lazyList->chunk(3);

        self::assertEquals(
            [
                [1, 2, 3],
                [4, 5, 6],
                [7, 8, 9],
                [10],
            ],
            $chunked->map(static fn ($chunk) => iterator_to_array($chunk))->toArray(),
        );
    }

    public function testHeadOnNotEmptyLazyList(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $head = $lazyList->head();
        self::assertTrue($head->isDefined());
        self::assertEquals(1, $head->getUnsafe());
    }

    public function testHeadOnEmptyLazyList(): void
    {
        /** @var iterable<int, int> $emptyIterable */
        $emptyIterable = [];
        $lazyList = new LazyList($emptyIterable);
        $head = $lazyList->head();
        self::assertFalse($head->isDefined());
    }

    public function testLastOnNotEmptyLazyList(): void
    {
        $lazyList = new LazyList(range(1, 10));
        $last = $lazyList->last();
        self::assertTrue($last->isDefined());
        self::assertEquals(10, $last->getUnsafe());
    }

    public function testLastOnEmptyLazyList(): void
    {
        /** @var iterable<int, int> $emptyIterable */
        $emptyIterable = [];
        $lazyList = new LazyList($emptyIterable);
        $last = $lazyList->last();
        self::assertFalse($last->isDefined());
    }

    public function testFilter(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $filtered = $lazyList->filter(static fn (int $item) => $item % 2 === 0);

        self::assertEquals(range(2, 10, 2), iterator_to_array($filtered));
    }

    public function testFindWhenItemExists(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $found = $lazyList->find(static fn (int $item) => $item % 2 === 0);

        self::assertTrue($found->isDefined());
        self::assertEquals(2, $found->getUnsafe());
    }

    public function testFindWhenItemDoesNotExist(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $found = $lazyList->find(static fn (int $item) => $item === 666);

        self::assertFalse($found->isDefined());
    }

    public function testDropWhile(): void
    {
        $lazyList = LazyList::range(1, 9)->concat(LazyList::range(0, 5));
        $rest = $lazyList->dropWhile(static fn (int $item) => $item < 5);

        self::assertEquals(array_merge(range(5, 9), range(0, 5)), $rest->toArray());
    }

    public function testDrop(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $rest = $lazyList->drop(2);

        self::assertEquals(range(3, 10), $rest->toArray());
    }

    public function testExists(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));

        self::assertTrue($lazyList->exists(static fn (int $item) => $item > 5));
        self::assertFalse($lazyList->exists(static fn (int $item) => $item > 10));
    }

    public function testAll(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(2, 10, 2)));

        self::assertTrue($lazyList->all(static fn (int $item) => $item < 11));
        self::assertTrue($lazyList->all(static fn (int $item) => $item % 2 === 0));
        self::assertFalse($lazyList->all(static fn (int $item) => $item < 10));
    }

    public function testZip(): void
    {
        $lazyList1 = new LazyList(new ArrayIterator(range(1, 10)));
        $lazyList2 = new LazyList(new ArrayIterator(range(11, 20)));

        self::assertEquals([
            [1, 11],
            [2, 12],
            [3, 13],
            [4, 14],
            [5, 15],
            [6, 16],
            [7, 17],
            [8, 18],
            [9, 19],
            [10, 20],
        ], $lazyList1->zip($lazyList2)->toArray());
    }

    public function testZipMap(): void
    {
        $ints = LazyList::range(0, 2);
        $map = $ints->zipMap(static fn (int $i): string => chr(ord('a') + $i));
        self::assertEquals(Map::fromIterable([[0, 'a'], [1, 'b'], [2, 'c']]), $map);
    }

    public function testConcat(): void
    {
        $lazyList1 = new LazyList(new ArrayIterator(range(1, 10)));
        $lazyList2 = new LazyList(new ArrayIterator(range(11, 20)));
        $lazyList3 = new LazyList(new ArrayIterator(range(21, 30)));

        self::assertEquals(range(1, 30), $lazyList1->concat($lazyList2, $lazyList3)->toArray());
    }

    public function testAdd(): void
    {
        $lazyList1 = new LazyList(new ArrayIterator(range(1, 10)));

        self::assertEquals(range(1, 13), $lazyList1->add(11, 12, 13)->toArray());
    }

    public function testInsertOnPosition(): void
    {
        /** @var LazyList<int|string> $lazyList1 */
        $lazyList1 = new LazyList([1, 2, 3]);
        $lazyList2 = new LazyList(['a', 'b']);
        self::assertEquals([1, 'a', 'b', 2, 3], $lazyList1->insertOnPosition(1, $lazyList2)->toArray());
    }

    public function testInsertOnInvalidPosition(): void
    {
        /** @var LazyList<int|string> $lazyList1 */
        $lazyList1 = new LazyList([1, 2, 3]);
        $lazyList2 = new LazyList(['a', 'b']);
        try {
            $lazyList1->insertOnPosition(7, $lazyList2)->toArray();
        } catch (InvalidArgumentException $exception) {
            self::assertEquals(
                'Tried to insert collection to position 7, but only 3 items were found',
                $exception->getMessage(),
            );
        }
    }

    public function testToArray(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));

        self::assertEquals(range(1, 3), $lazyList->take(3)->toArray());
    }

    public function testToMap(): void
    {
        $pairs = LazyList::of([1, 'a'], [2, 'b']);
        self::assertEquals(Map::fromIterable([[1, 'a'], [2, 'b']]), $pairs->toMap());
    }

    public function testJoin(): void
    {
        $lazyList = new LazyList([1, 2, 3]);
        self::assertEquals('1, 2, 3', $lazyList->join(', '));
    }

    public function testLazyListLazinessChaining(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $numberOfCalls = 0;
        $mapped = $lazyList->map(static function ($item) use (&$numberOfCalls) {
            $numberOfCalls++;
            return $item * 10;
        });

        self::assertEquals(0, $numberOfCalls);
        $taken = $mapped->take(3);
        self::assertEquals(0, $numberOfCalls);
        self::assertEquals(range(10, 30, 10), iterator_to_array($taken));
        self::assertEquals(3, $numberOfCalls);
    }
}
