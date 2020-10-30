<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use EmptyIterator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class LazyListTest extends TestCase
{

    public function testRange(): void
    {
        $range = LazyList::range(3, 30, 3);
        $this->assertEquals(range(3, 30, 3), $range->toArray());
    }

    public function testInfinityRange(): void
    {
        $range = LazyList::range(1)
            ->filter(function (int $number): bool {
                return $number % 3 === 0;
            })
            ->take(10)
            ->toArray();
        $this->assertEquals(range(3, 30, 3), $range);
    }

    public function testFill(): void
    {
        $fill = LazyList::fill('a', 2);
        $this->assertEquals(['a', 'a'], $fill->toArray());
    }

    public function testInfinityFill(): void
    {
        $filled = LazyList::fill("a")
            ->take(10)
            ->toArray();
        $this->assertEquals(array_fill(0, 10, "a"), $filled);
    }

    public function testFromArray(): void
    {
        $seq = LazyList::fromArray([1, 2, 3]);
        $this->assertEquals([1, 2, 3], $seq->toArray());
    }

    public function testFromArrays(): void
    {
        $seq = LazyList::fromArray([1, 2, 3], [4, 5, 6], [7]);
        $this->assertEquals([1, 2, 3, 4, 5, 6, 7], $seq->toArray());
    }

    public function testFromTraversable(): void
    {
        $seq = LazyList::fromTraversable(new ArrayIterator([1, 2, 3]));
        $this->assertEquals([1, 2, 3], $seq->toArray());
    }

    public function testFromIterable(): void
    {
        $lazyList = LazyList::fromIterable(new ArrayIterator([1, 2]));
        $this->assertEquals([1, 2], $lazyList->toArray());
    }

    public function testFromItems(): void
    {
        $lazyList = LazyList::of(1, 2);
        $this->assertEquals([1, 2], $lazyList->toArray());
    }

    public function testMap(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $mapped = $lazyList->map(function ($item) {
            return $item * 10;
        });

        $this->assertEquals(range(10, 100, 10), iterator_to_array($mapped));
    }

    public function testMapWithKey(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10, 2)));
        $mapped = $lazyList->map(function ($item, $index) {
            return $index;
        });

        $this->assertEquals(range(0, 4), iterator_to_array($mapped));
    }

    public function testAp(): void
    {
        $callbacks = LazyList::of(
            function ($a, $b) {
                return $a . $b;
            },
            function ($a, $b) {
                return [$a, $b];
            }
        );
        $mapped = $callbacks
            ->ap(LazyList::of(1, 2))
            ->ap(LazyList::of('a', 'b'));

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
        $callbacks = LazyList::of(
            function ($a, $b) {
                return $a . $b;
            },
            function ($a, $b) {
                return [$a, $b];
            }
        );
        $mapped = $callbacks
            ->ap(LazyList::of(1, 2))
            ->ap(LazyList::fromEmpty());

        $this->assertEquals([], $mapped->toArray());
    }

    public function testLift(): void
    {
        $lifted = LazyList::lift(function ($a, $b) {
            return $a . $b;
        });

        $mapped = $lifted(LazyList::of(1, 2), LazyList::of('a', 'b'));

        $this->assertEquals(['1a', '2a', '1b', '2b'], $mapped->toArray());
    }

    public function testSequence(): void
    {
        $this->assertEquals(
            [ArrayList::of(1, 2)],
            LazyList::sequence([LazyList::of(1), LazyList::of(2)])->toArray()
        );
        $this->assertEquals(
            [],
            LazyList::sequence([LazyList::of(1), LazyList::fromEmpty()])->toArray()
        );
        /** @phpstan-var array<LazyList<int>> $empty */
        $empty = [];
        $this->assertEquals(
            [ArrayList::fromEmpty()],
            LazyList::sequence($empty)->toArray()
        );
    }

    public function testSequenceWithMultipleValues(): void
    {
        $this->assertEquals(
            [
                ArrayList::of(1, 'a'),
                ArrayList::of(2, 'a'),
                ArrayList::of(1, 'b'),
                ArrayList::of(2, 'b'),
            ],
            LazyList::sequence([LazyList::of(1, 2), LazyList::of('a', 'b')])->toArray()
        );
    }

    public function testFlatMap(): void
    {
        $lazyList = new LazyList([1, 2, 3]);
        $mapped = $lazyList->flatMap(function (int $item): array {
            return [$item, [$item * 2]];
        });
        $this->assertEquals([1, [2], 2, [4], 3, [6]], $mapped->toArray());
    }

    public function testFlatten(): void
    {
        $lazyList = new LazyList([[[1], [2]], [[3]]]);
        $this->assertEquals([[1], [2], [3]], $lazyList->flatten()->toArray());
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
        $lazyList->each(function (int $item) use (&$accumulator): void {
            $accumulator += $item;
        });

        $this->assertEquals(55, $accumulator);
    }

    public function testReduce(): void
    {
        $lazyList = new LazyList(range(1, 3));

        $sum = $lazyList->reduce(function ($sum, $item) {
            return $sum + $item;
        }, 0);
        $this->assertEquals(6, $sum);
    }

    public function testScan(): void
    {
        $lazyList = LazyList::fill(1);

        $sum = $lazyList->scan(function ($sum, $item) {
            return $sum + $item;
        }, 0);
        $this->assertEquals([1, 2, 3], $sum->take(3)->toArray());
    }

    public function testMapRepeatedCall(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $mapped1 = $lazyList->map(function ($item) {
            return $item * 10;
        });
        $mapped2 = $lazyList->map(function ($item) {
            return $item * 100;
        });

        $this->assertEquals(range(10, 100, 10), iterator_to_array($mapped1));
        $this->assertEquals(range(100, 1000, 100), iterator_to_array($mapped2));
    }

    public function testTakeWhile(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $taken = $lazyList->takeWhile(function (int $i): bool {
            return $i <= 3;
        });

        $this->assertEquals(range(1, 3), iterator_to_array($taken));
    }

    public function testTake(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $taken = $lazyList->take(5);

        $this->assertEquals(range(1, 5), iterator_to_array($taken));
    }

    public function testTakeFilteredInfiniteLazyList(): void
    {
        $generator = function () {
            $i = 0;
            while (true) {
                yield $i++;
            }
        };

        $lazyList = new LazyList($generator());
        $taken = $lazyList->filter(function ($x) {
            return $x < 10;
        })->filter(function ($x) {
            return $x >= 5;
        })->take(5);

        $this->assertEquals(5, iterator_count($taken));
    }

    public function testChunk(): void
    {
        $lazyList = LazyList::range(1, 10);
        $chunked = $lazyList->chunk(3);

        $this->assertEquals(
            [
                [1, 2, 3],
                [4, 5, 6],
                [7, 8, 9],
                [10],
            ],
            $chunked->map(function ($chunk) {
                return iterator_to_array($chunk);
            })->toArray()
        );
    }

    public function testHeadOnNotEmptyLazyList(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $head = $lazyList->head();
        $this->assertInstanceOf(Option::class, $head);
        $this->assertTrue($head->isDefined());
        $this->assertEquals(1, $head->getUnsafe());
    }

    public function testHeadOnEmptyLazyList(): void
    {
        $lazyList = new LazyList(new EmptyIterator());
        $head = $lazyList->head();
        $this->assertInstanceOf(Option::class, $head);
        $this->assertFalse($head->isDefined());
    }

    public function testLastOnNotEmptyLazyList(): void
    {
        $lazyList = new LazyList(range(1, 10));
        $last = $lazyList->last();
        $this->assertInstanceOf(Option::class, $last);
        $this->assertTrue($last->isDefined());
        $this->assertEquals(10, $last->getUnsafe());
    }

    public function testLastOnEmptyLazyList(): void
    {
        $lazyList = new LazyList(new EmptyIterator());
        $last = $lazyList->last();
        $this->assertInstanceOf(Option::class, $last);
        $this->assertFalse($last->isDefined());
    }

    public function testFilter(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $filtered = $lazyList->filter(function (int $item) {
            return $item % 2 == 0;
        });

        $this->assertEquals(range(2, 10, 2), iterator_to_array($filtered));
    }

    public function testFindWhenItemExists(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $found = $lazyList->find(function (int $item) {
            return $item % 2 == 0;
        });

        $this->assertInstanceOf(Option::class, $found);
        $this->assertTrue($found->isDefined());
        $this->assertEquals(2, $found->getUnsafe());
    }

    public function testFindWhenItemDoesNotExist(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $found = $lazyList->find(function (int $item) {
            return $item === 666;
        });

        $this->assertInstanceOf(Option::class, $found);
        $this->assertFalse($found->isDefined());
    }

    public function testDropWhile(): void
    {
        $lazyList = LazyList::range(1, 9)->concat(LazyList::range(0, 5));
        $rest = $lazyList->dropWhile(function (int $item) {
            return $item < 5;
        });

        $this->assertEquals(array_merge(range(5, 9), range(0, 5)), $rest->toArray());
    }

    public function testDrop(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $rest = $lazyList->drop(2);

        $this->assertEquals(range(3, 10), $rest->toArray());
    }

    public function testExists(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));

        $this->assertTrue($lazyList->exists(function (int $item) {
            return $item > 5;
        }));
        $this->assertFalse($lazyList->exists(function (int $item) {
            return $item > 10;
        }));
    }

    public function testAll(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(2, 10, 2)));

        $this->assertTrue($lazyList->all(function (int $item) {
            return $item < 11;
        }));
        $this->assertTrue($lazyList->all(function (int $item) {
            return $item % 2 == 0;
        }));
        $this->assertFalse($lazyList->all(function (int $item) {
            return $item < 10;
        }));
    }

    public function testZip(): void
    {
        $lazyList1 = new LazyList(new ArrayIterator(range(1, 10, 1)));
        $lazyList2 = new LazyList(new ArrayIterator(range(11, 20, 1)));

        $this->assertEquals([
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
        $map = $ints->zipMap(function (int $i): string {
            return chr(ord('a') + $i);
        });
        $this->assertEquals(Map::fromIterable([[0, 'a'], [1, 'b'], [2, 'c']]), $map);
    }

    public function testConcat(): void
    {
        $lazyList1 = new LazyList(new ArrayIterator(range(1, 10, 1)));
        $lazyList2 = new LazyList(new ArrayIterator(range(11, 20, 1)));
        $lazyList3 = new LazyList(new ArrayIterator(range(21, 30, 1)));

        $this->assertEquals(range(1, 30), $lazyList1->concat($lazyList2, $lazyList3)->toArray());
    }

    public function testAdd(): void
    {
        $lazyList1 = new LazyList(new ArrayIterator(range(1, 10, 1)));

        $this->assertEquals(range(1, 13), $lazyList1->add(11, 12, 13)->toArray());
    }

    public function testInsertOnPosition(): void
    {
        /** @var LazyList<int|string> $lazyList1 */
        $lazyList1 = new LazyList([1, 2, 3]);
        $lazyList2 = new LazyList(['a', 'b']);
        $this->assertEquals([1, 'a', 'b', 2, 3], $lazyList1->insertOnPosition(1, $lazyList2)->toArray());
    }

    public function testInsertOnInvalidPosition(): void
    {
        /** @var LazyList<int|string> $lazyList1 */
        $lazyList1 = new LazyList([1, 2, 3]);
        $lazyList2 = new LazyList(['a', 'b']);
        try {
            $lazyList1->insertOnPosition(7, $lazyList2)->toArray();
        } catch (InvalidArgumentException $exception) {
            $this->assertEquals(
                "Tried to insert collection to position 7, but only 3 items were found",
                $exception->getMessage()
            );
        }
    }

    public function testToArray(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));

        $this->assertEquals(range(1, 3), $lazyList->take(3)->toArray());
    }

    public function testToMap(): void
    {
        $pairs = LazyList::of([1, 'a'], [2, 'b']);
        $this->assertEquals(Map::fromIterable([[1, 'a'], [2, 'b']]), $pairs->toMap());
    }

    public function testJoin(): void
    {
        $lazyList = new LazyList([1, 2, 3]);
        $this->assertEquals('1, 2, 3', $lazyList->join(", "));
    }

    public function testLazyListLazinessChaining(): void
    {
        $lazyList = new LazyList(new ArrayIterator(range(1, 10)));
        $numberOfCalls = 0;
        $mapped = $lazyList->map(function ($item) use (&$numberOfCalls) {
            $numberOfCalls++;
            return $item * 10;
        });

        $this->assertEquals(0, $numberOfCalls);
        $taken = $mapped->take(3);
        $this->assertEquals(0, $numberOfCalls);
        $this->assertEquals(range(10, 30, 10), iterator_to_array($taken));
        $this->assertEquals(3, $numberOfCalls);
    }
}
