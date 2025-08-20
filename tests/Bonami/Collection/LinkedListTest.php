<?php

declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\OutOfBoundsException;
use Bonami\Collection\Monoid\IntSumMonoid;
use PHPUnit\Framework\TestCase;
use stdClass;

class LinkedListTest extends TestCase
{
    public function testFromEmpty(): void
    {
        self::assertEquals([], LinkedList::fromEmpty()->toArray());
    }

    public function testFromItems(): void
    {
        self::assertEquals([1], LinkedList::of(1)->toArray());
        self::assertEquals([1, 2], LinkedList::of(1, 2)->toArray());
    }

    public function testPure(): void
    {
        self::assertEquals([42], LinkedList::pure(42)->toArray());
    }

    public function testFromIterable(): void
    {
        self::assertEquals(LinkedList::of(1, 2, 3), LinkedList::fromIterable([1, 2, 3]));
        self::assertEquals(LinkedList::of(1, 2, 3), LinkedList::fromIterable(new ArrayList([1, 2, 3])));
        self::assertEquals(LinkedList::of(1, 2, 3), LinkedList::fromIterable(new ArrayIterator([1, 2, 3])));
    }

    public function testCountable(): void
    {
        self::assertCount(2, LinkedList::of(1, 2));
        self::assertCount(0, LinkedList::fromEmpty());
    }

    public function testIsEmpty(): void
    {
        self::assertTrue(LinkedList::fromEmpty()->isEmpty());
        self::assertFalse(LinkedList::of(1, 2)->isEmpty());
    }

    public function testIsNotEmpty(): void
    {
        self::assertFalse(LinkedList::fromEmpty()->isNotEmpty());
        self::assertTrue(LinkedList::of(1, 2)->isNotEmpty());
    }

    public function testGet(): void
    {
        $list = LinkedList::fromIterable([666, null]);
        self::assertEquals(Option::some(666), $list->get(0));
        self::assertEquals(Option::some(null), $list->get(1));
        self::assertEquals(Option::none(), $list->get(2));
    }

    public function testGetOrElse(): void
    {
        $list = LinkedList::fromIterable([666, null]);
        self::assertSame(666, $list->getOrElse(0, 42));
        self::assertSame(null, $list->getOrElse(1, 42));
        self::assertSame(42, $list->getOrElse(2, 42));
    }

    public function testGetUnsafe(): void
    {
        $list = LinkedList::of(666);
        self::assertSame(666, $list->getUnsafe(0));

        $this->expectException(OutOfBoundsException::class);
        $list->getUnsafe(1);
    }

    public function testMap(): void
    {
        $mapped = LinkedList::of(3, 2, 1, 0)->map(static fn ($i, $key) => $i + $key);
        self::assertEquals(LinkedList::of(3, 3, 3, 3), $mapped);
    }

    public function testFlatMap(): void
    {
        $flatMapped = LinkedList::of(1, 2)->flatMap(static fn ($i) => [$i, $i * 2]);
        self::assertEquals(LinkedList::of(1, 2, 2, 4), $flatMapped);
    }

    public function testFlatten(): void
    {
        $flattened = LinkedList::of([1, 2], [3, 4])->flatten();
        self::assertEquals(LinkedList::of(1, 2, 3, 4), $flattened);
    }

    public function testFilter(): void
    {
        $filtered = LinkedList::of(1, 2, 3, 4)->filter(static fn ($i) => $i % 2 === 0);
        self::assertEquals(LinkedList::of(2, 4), $filtered);
    }

    public function testFind(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertEquals(Option::some(3), $list->find(static fn ($i) => $i > 2));
        self::assertEquals(Option::none(), $list->find(static fn ($i) => $i > 10));
        self::assertEquals(Option::none(), LinkedList::fromEmpty()->find(static fn ($i) => $i > 2));
    }

    public function testFindKey(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertEquals(Option::some(2), $list->findKey(static fn ($i) => $i > 2));
        self::assertEquals(Option::none(), $list->findKey(static fn ($i) => $i > 10));
        self::assertEquals(Option::none(), LinkedList::fromEmpty()->findKey(static fn ($i) => $i > 2));
    }

    public function testExists(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->exists(static fn ($i) => $i > 2));
        self::assertFalse($list->exists(static fn ($i) => $i > 10));
        self::assertFalse(LinkedList::fromEmpty()->exists(static fn ($i) => $i > 0));
    }

    public function testContains(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->contains(3));
        self::assertFalse($list->contains('3'));
        self::assertFalse($list->contains(10));
    }

    public function testContainsNonStrict(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->contains(3, false));
        self::assertTrue($list->contains('3', false));
        self::assertFalse($list->contains(10, false));
    }

    public function testAll(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->all(static fn ($i) => $i > 0));
        self::assertFalse($list->all(static fn ($i) => $i > 2));
        self::assertTrue(LinkedList::fromEmpty()->all(static fn ($i) => $i > 0));
    }

    public function testReduce(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        $sum = $list->reduce(static fn ($a, $b) => $a + $b, 0);
        self::assertSame(10, $sum);
    }

    public function testMfold(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        $sum = $list->mfold(new IntSumMonoid());
        self::assertSame(10, $sum);
    }

    public function testSum(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        $sum = $list->sum(static fn ($i) => $i);
        self::assertSame(10, $sum);
    }

    public function testMin(): void
    {
        $list = LinkedList::of(3, 1, 4, 2);
        self::assertEquals(Option::some(1), $list->min());
        self::assertEquals(Option::none(), LinkedList::fromEmpty()->min());
    }

    public function testMax(): void
    {
        $list = LinkedList::of(3, 1, 4, 2);
        self::assertEquals(Option::some(4), $list->max());
        self::assertEquals(Option::none(), LinkedList::fromEmpty()->max());
    }

    public function testEach(): void
    {
        $list = LinkedList::of(1, 2, 3);
        $sum = 0;
        $list->each(static function ($item) use (&$sum) {
            $sum += $item;
        });
        self::assertSame(6, $sum);
    }

    public function testTap(): void
    {
        $list = LinkedList::of(1, 2, 3);
        $sum = 0;
        $result = $list->tap(static function ($item) use (&$sum) {
            $sum += $item;
        });
        self::assertSame(6, $sum);
        self::assertSame($list, $result);
    }

    public function testHead(): void
    {
        $list = LinkedList::of(1, 2, 3);
        self::assertEquals(Option::some(1), $list->head());
        self::assertEquals(Option::none(), LinkedList::fromEmpty()->head());
    }

    public function testLast(): void
    {
        $list = LinkedList::of(1, 2, 3);
        self::assertEquals(Option::some(3), $list->last());
        self::assertEquals(Option::none(), LinkedList::fromEmpty()->last());
    }

    public function testTake(): void
    {
        $list = LinkedList::of(1, 2, 3, 4, 5);
        self::assertEquals(LinkedList::of(1, 2, 3), $list->take(3));
        self::assertEquals(LinkedList::of(1, 2, 3, 4, 5), $list->take(10));
        self::assertEquals(LinkedList::fromEmpty(), $list->take(0));
    }

    public function testSlice(): void
    {
        $list = LinkedList::of(1, 2, 3, 4, 5);
        self::assertEquals(LinkedList::of(2, 3), $list->slice(1, 2));
        self::assertEquals(LinkedList::of(3, 4, 5), $list->slice(2));
        self::assertEquals(LinkedList::fromEmpty(), $list->slice(10));
    }

    public function testWithoutNulls(): void
    {
        $list = LinkedList::of(1, null, 2, null, 3);
        self::assertEquals(LinkedList::of(1, 2, 3), $list->withoutNulls());
    }

    public function testConcat(): void
    {
        $list1 = LinkedList::of(1, 2);
        $list2 = LinkedList::of(3, 4);
        self::assertEquals(LinkedList::of(1, 2, 3, 4), $list1->concat($list2));
    }

    public function testConcatPreservesImmutability(): void
    {
        $original1 = LinkedList::of(1, 2);
        $original2 = LinkedList::of(3, 4);
        
        // Store original state for comparison
        $original1Array = $original1->toArray();
        $original2Array = $original2->toArray();
        
        // Perform concatenation
        $concatenated = $original1->concat($original2);
        
        // Verify original lists are unchanged
        self::assertEquals(
            $original1Array,
            $original1->toArray(),
            'First list should remain unchanged after concatenation',
        );
        self::assertEquals(
            $original2Array,
            $original2->toArray(),
            'Second list should remain unchanged after concatenation',
        );
        
        // Verify concatenated result is correct
        self::assertEquals([1, 2, 3, 4], $concatenated->toArray());
        
        // Verify that modifying elements through iteration doesn't affect original lists
        $sum = 0;
        $concatenated->each(static function ($item) use (&$sum) {
            $sum += $item;
        });
        
        // Original lists should still be unchanged
        self::assertEquals(
            $original1Array,
            $original1->toArray(),
            'First list should remain unchanged after iteration',
        );
        self::assertEquals(
            $original2Array,
            $original2->toArray(),
            'Second list should remain unchanged after iteration',
        );
        
        // Test concatenation with arrays
        $arrayConcat = $original1->concat([5, 6]);
        self::assertEquals([1, 2, 5, 6], $arrayConcat->toArray());
        self::assertEquals(
            $original1Array,
            $original1->toArray(),
            'First list should remain unchanged after array concatenation',
        );
    }

    public function testPrependPreservesImmutability(): void
    {
        $original = LinkedList::of(3, 4);
        $originalArray = $original->toArray();
        
        $prepended = $original->prepend(1, 2);
        
        self::assertEquals(
            $originalArray,
            $original->toArray(),
            'Original list should remain unchanged after prepend',
        );
        
        self::assertEquals([1, 2, 3, 4], $prepended->toArray());
    }

    public function testAdd(): void
    {
        $list = LinkedList::of(1, 2);
        $result = $list->add(3, 4);
        self::assertEquals(LinkedList::of(1, 2, 3, 4), $result);
    }

    public function testPrepend(): void
    {
        $list = LinkedList::of(3, 4);
        $result = $list->prepend(1, 2);
        self::assertEquals(LinkedList::of(1, 2, 3, 4), $result);
    }

    public function testReverse(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertEquals(LinkedList::of(4, 3, 2, 1), $list->reverse());
    }

    public function testToArray(): void
    {
        $list = LinkedList::of(1, 2, 3);
        self::assertEquals([1, 2, 3], $list->toArray());
    }

    public function testToList(): void
    {
        $linkedList = LinkedList::of(1, 2, 3);
        $arrayList = $linkedList->toList();
        self::assertInstanceOf(ArrayList::class, $arrayList);
        self::assertEquals(ArrayList::of(1, 2, 3), $arrayList);
    }

    public function testLazy(): void
    {
        $linkedList = LinkedList::of(1, 2, 3);
        $lazyList = $linkedList->lazy();
        self::assertInstanceOf(LazyList::class, $lazyList);
        self::assertEquals([1, 2, 3], $lazyList->toArray());
    }

    public function testJoin(): void
    {
        $list = LinkedList::of(1, 2, 3);
        self::assertEquals('1,2,3', $list->join(','));
        self::assertEquals('123', $list->join(''));
    }

    public function testJsonSerialize(): void
    {
        $list = LinkedList::of(1, 2, 3);
        self::assertEquals([1, 2, 3], $list->jsonSerialize());
    }

    public function testToString(): void
    {
        $list = LinkedList::of(1, 2, 3);
        self::assertEquals('[1, 2, 3]', (string) $list);
    }

    public function testIterator(): void
    {
        $list = LinkedList::of(1, 2, 3);
        $iterator = $list->getIterator();
        self::assertInstanceOf(\Iterator::class, $iterator);
        
        $result = [];
        foreach ($iterator as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertEquals([0 => 1, 1 => 2, 2 => 3], $result);
    }

    public function testForeach(): void
    {
        $list = LinkedList::of(1, 2, 3);
        $result = [];
        foreach ($list as $key => $value) {
            $result[$key] = $value;
        }
        
        self::assertEquals([0 => 1, 1 => 2, 2 => 3], $result);
    }

    public function testComplexOperations(): void
    {
        $list = LinkedList::of(1, 2, 3, 4, 5, 6, 7, 8, 9, 10);
        
        $result = $list
            ->filter(static fn ($i) => $i % 2 === 0)
            ->map(static fn ($i) => $i * 2)
            ->take(3)
            ->reverse();
        
        self::assertEquals(LinkedList::of(12, 8, 4), $result);
    }
}
