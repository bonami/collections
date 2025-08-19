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
        self::assertEquals(LinkedList::fromEmpty(), LinkedList::fromEmpty());
    }

    public function testFromItems(): void
    {
        self::assertEquals(LinkedList::of(1), LinkedList::of(1));
        self::assertEquals(LinkedList::of(1, 2), LinkedList::of(1, 2));
    }

    public function testPure(): void
    {
        self::assertEquals(LinkedList::of(42), LinkedList::pure(42));
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
        $list = LinkedList::of(666);
        self::assertTrue($list->get(0)->equals(Option::some(666)));
        self::assertTrue($list->get(1)->equals(Option::none()));
    }

    public function testGetOrElse(): void
    {
        $list = LinkedList::of(666);
        self::assertSame(666, $list->getOrElse(0, 42));
        self::assertSame(42, $list->getOrElse(1, 42));
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
        self::assertTrue($list->find(static fn ($i) => $i > 2)->equals(Option::some(3)));
        self::assertTrue($list->find(static fn ($i) => $i > 10)->equals(Option::none()));
    }

    public function testFindKey(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->findKey(static fn ($i) => $i > 2)->equals(Option::some(2)));
        self::assertTrue($list->findKey(static fn ($i) => $i > 10)->equals(Option::none()));
    }

    public function testExists(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->exists(static fn ($i) => $i > 2));
        self::assertFalse($list->exists(static fn ($i) => $i > 10));
    }

    public function testContains(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->contains(3));
        self::assertFalse($list->contains(10));
    }

    public function testAll(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        self::assertTrue($list->all(static fn ($i) => $i > 0));
        self::assertFalse($list->all(static fn ($i) => $i > 2));
    }

    public function testReduce(): void
    {
        $list = LinkedList::of(1, 2, 3, 4);
        $sum = $list->reduce(static fn ($carry, $item) => $carry + $item, 0);
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
        self::assertTrue($list->min()->equals(Option::some(1)));
        self::assertTrue(LinkedList::fromEmpty()->min()->equals(Option::none()));
    }

    public function testMax(): void
    {
        $list = LinkedList::of(3, 1, 4, 2);
        self::assertTrue($list->max()->equals(Option::some(4)));
        self::assertTrue(LinkedList::fromEmpty()->max()->equals(Option::none()));
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
        self::assertTrue($list->head()->equals(Option::some(1)));
        self::assertTrue(LinkedList::fromEmpty()->head()->equals(Option::none()));
    }

    public function testLast(): void
    {
        $list = LinkedList::of(1, 2, 3);
        self::assertTrue($list->last()->equals(Option::some(3)));
        self::assertTrue(LinkedList::fromEmpty()->last()->equals(Option::none()));
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
        
        // Perform prepend operation
        $prepended = $original->prepend(1, 2);
        
        // Verify original list is unchanged
        self::assertEquals(
            $originalArray,
            $original->toArray(),
            'Original list should remain unchanged after prepend',
        );
        
        // Verify prepended result is correct
        self::assertEquals([1, 2, 3, 4], $prepended->toArray());
        
        // Test that subsequent operations on the prepended list don't affect original
        $furtherPrepended = $prepended->prepend(0);
        self::assertEquals(
            $originalArray,
            $original->toArray(),
            'Original list should remain unchanged after further operations',
        );
        self::assertEquals([1, 2, 3, 4], $prepended->toArray(), 'First prepended list should remain unchanged');
        self::assertEquals([0, 1, 2, 3, 4], $furtherPrepended->toArray());
    }

    public function testStructuralSharingDoesNotBreakImmutability(): void
    {
        // Create two lists
        $list1 = LinkedList::of(1, 2);
        $list2 = LinkedList::of(3, 4);
        
        // Concatenate them (this uses structural sharing)
        $concatenated = $list1->concat($list2);
        
        // Create a new list from the second list
        $list2Modified = $list2->prepend(0);
        
        // Verify that modifications to list2 don't affect the concatenated result
        self::assertEquals(
            [1, 2, 3, 4],
            $concatenated->toArray(),
            'Concatenated list should not be affected by modifications to source lists',
        );
        self::assertEquals([3, 4], $list2->toArray(), 'Original list2 should remain unchanged');
        self::assertEquals([0, 3, 4], $list2Modified->toArray(), 'Modified list2 should contain the new element');
        
        // Verify that the concatenated list remains independent
        $concatenatedModified = $concatenated->prepend(-1);
        self::assertEquals(
            [1, 2, 3, 4],
            $concatenated->toArray(),
            'Original concatenated list should remain unchanged',
        );
        self::assertEquals(
            [-1, 1, 2, 3, 4],
            $concatenatedModified->toArray(),
            'Modified concatenated list should contain the new element',
        );
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
        self::assertEquals([1, 2, 3], $arrayList->toArray());
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

    public function testEmptyListOperations(): void
    {
        $empty = LinkedList::fromEmpty();
        
        self::assertTrue($empty->isEmpty());
        self::assertCount(0, $empty);
        self::assertTrue($empty->head()->equals(Option::none()));
        self::assertTrue($empty->last()->equals(Option::none()));
        self::assertTrue($empty->find(static fn () => true)->equals(Option::none()));
        self::assertTrue($empty->findKey(static fn () => true)->equals(Option::none()));
        self::assertFalse($empty->exists(static fn () => true));
        self::assertTrue($empty->all(static fn () => true));
        self::assertEquals(LinkedList::fromEmpty(), $empty->take(5));
        self::assertEquals(LinkedList::fromEmpty(), $empty->slice(0, 5));
    }

    public function testSingleItemList(): void
    {
        $single = LinkedList::of(42);
        
        self::assertFalse($single->isEmpty());
        self::assertCount(1, $single);
        self::assertTrue($single->head()->equals(Option::some(42)));
        self::assertTrue($single->last()->equals(Option::some(42)));
        self::assertSame(42, $single->getUnsafe(0));
        self::assertTrue($single->get(1)->equals(Option::none()));
    }

    public function testObjectValues(): void
    {
        $obj1 = new stdClass();
        $obj1->value = 'test1';
        $obj2 = new stdClass();
        $obj2->value = 'test2';
        
        $list = LinkedList::of($obj1, $obj2);
        
        self::assertCount(2, $list);
        self::assertSame($obj1, $list->getUnsafe(0));
        self::assertSame($obj2, $list->getUnsafe(1));
        
        $mapped = $list->map(static fn ($obj) => $obj->value);
        self::assertEquals(LinkedList::of('test1', 'test2'), $mapped);
    }
}
