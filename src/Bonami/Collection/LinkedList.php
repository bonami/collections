<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\NotImplementedException;
use Bonami\Collection\Exception\OutOfBoundsException;
use Bonami\Collection\LinkedList\LinkedListIterator;
use Bonami\Collection\LinkedList\Node;
use Bonami\Collection\Monoid\Monoid;
use Countable;
use Iterator;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

use function array_values;
use function count;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_object;
use function iterator_to_array;
use function method_exists;
use function spl_object_hash;
use function sprintf;

/**
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
class LinkedList implements Countable, IteratorAggregate, JsonSerializable
{
    /** @use Monad1<T> */
    use Monad1;
    /** @use Iterable1<T> */
    use Iterable1;

    /** @var Node<T>|null */
    private $head;

    /** @var int */
    private $size;

    /**
     * @param Node<T>|null $head
     * @param int $size
     */
    final private function __construct(?Node $head, int $size)
    {
        $this->head = $head;
        $this->size = $size;
    }

    /**
     * Creates an empty LinkedList
     *
     * Complexity: O(1)
     *
     * @return static<T>
     */
    public static function fromEmpty()
    {
        return new static(null, 0);
    }

    /**
     * Creates a LinkedList with each enlisted item
     *
     * Complexity: O(n) - where n is number of passed items
     *
     * @template V
     *
     * @param V ...$item - with any number of occurrences
     *
     * @return static<V>
     */
    public static function of(...$item)
    {
        $head = null;
        $size = 0;
        
        // Build the list in reverse order for O(1) prepend
        for ($i = count($item) - 1; $i >= 0; $i--) {
            $head = new Node($item[$i], $head);
            $size++;
        }
        
        return new static($head, $size);
    }

    /**
     * Creates a LinkedList from single item
     *
     * Complexity: O(1)
     *
     * @template V
     *
     * @param V $item
     *
     * @return static<V>
     */
    public static function pure($item)
    {
        return new static(new Node($item, null), 1);
    }

    /**
     * Creates a LinkedList from any iterable collection
     *
     * Complexity: O(n) - where n is size of iterable
     *
     * @template V
     *
     * @param iterable<int, V> $iterable
     *
     * @return static<V>
     */
    public static function fromIterable(iterable $iterable)
    {
        $items = self::convertIterableToArray($iterable);
        return self::of(...$items);
    }

    /**
     * Internal helper for converting iterable collection into array
     *
     * @template V
     *
     * @param iterable<int, V> $iterable
     *
     * @return array<V>
     */
    private static function convertIterableToArray(iterable $iterable): array
    {
        return match (true) {
            is_array($iterable) => $iterable,
            $iterable instanceof self => $iterable->toArray(),
            $iterable instanceof ArrayList => $iterable->toArray(),
            $iterable instanceof Map => $iterable->values()->toArray(),
            default => iterator_to_array($iterable, false),
        };
    }

    /**
     * Returns rewindable iterator
     *
     * Complexity: O(1) - Retrieving the iterator itself is constant
     *
     * @return Iterator<int, T>
     */
    public function getIterator(): Iterator
    {
        return new LinkedListIterator($this->head);
    }

    /**
     * Returns size of LinkedList
     *
     * Complexity: O(1)
     *
     * @return int
     */
    public function count(): int
    {
        return $this->size;
    }

    /**
     * Checks if the LinkedList is empty
     *
     * Complexity: O(1)
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->size === 0;
    }

    /**
     * Checks if the LinkedList is not empty
     *
     * Complexity: O(1)
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->size !== 0;
    }

    /**
     * Safely gets item in list by key wrapped in Option
     *
     * Complexity: O(n) - where n is the key position
     *
     * @param int $key
     *
     * @return Option<T>
     */
    public function get(int $key): Option
    {
        if ($key < 0 || $key >= $this->size) {
            return Option::none();
        }

        $current = $this->head;
        for ($i = 0; $i < $key && $current !== null; $i++) {
            $current = $current->next;
        }

        if ($current !== null) {
            return Option::some($current->value);
        }
        
        return Option::none();
    }

    /**
     * Safely gets unwrapped item or alternative when key is not defined
     *
     * Complexity: O(n) - where n is the key position
     *
     * @template E
     *
     * @param int $key
     * @param E $else
     *
     * @return T|E
     */
    public function getOrElse(int $key, $else)
    {
        return $this->get($key)->getOrElse($else);
    }

    /**
     * Gets unwrapped item or fails with exception when key is not defined
     *
     * @param int $key
     *
     * @throws OutOfBoundsException
     *
     * @return T
     */
    public function getUnsafe(int $key)
    {
        $option = $this->get($key);
        if ($option->isDefined()) {
            return $option->getUnsafe();
        }

        throw new OutOfBoundsException(sprintf('Key [%d] does not exist', $key));
    }

    /**
     * Constructs a LinkedList containing all elements after applying callback
     * on each value
     *
     * Complexity: O(n)
     *
     * @template B
     *
     * @param callable(T, int): B $mapper
     *
     * @return static<B>
     */
    public function map(callable $mapper): self
    {
        $result = [];
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            $result[] = $mapper($current->value, $index);
            $current = $current->next;
            $index++;
        }
        
        return self::of(...$result);
    }

    /**
     * Creates a new LinkedList as result of mapping and then flattening the result
     *
     * Complexity: O(m) - where m is a number of resulting items
     *
     * @template B
     *
     * @param callable(T, int): iterable<B> $mapper
     *
     * @return static<B>
     */
    public function flatMap(callable $mapper): self
    {
        $result = [];
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            $mapped = $mapper($current->value, $index);
            foreach ($mapped as $item) {
                $result[] = $item;
            }
            $current = $current->next;
            $index++;
        }
        
        return self::of(...$result);
    }

    /**
     * Flattens LinkedList of iterables into LinkedList
     *
     * Complexity: O(m) - where m is a number of resulting items
     *
     * @return static<mixed>
     */
    public function flatten(): self
    {
        $result = [];
        $current = $this->head;
        
        while ($current !== null) {
            if (is_iterable($current->value)) {
                foreach ($current->value as $item) {
                    $result[] = $item;
                }
            } else {
                $result[] = $current->value;
            }
            $current = $current->next;
        }
        
        return self::of(...$result);
    }

    /**
     * Filters out LinkedList by given predicate
     *
     * Complexity: O(n)
     *
     * @param callable(T, int): bool $predicate
     *
     * @return static<T>
     */
    public function filter(callable $predicate): self
    {
        $result = [];
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            if ($predicate($current->value, $index)) {
                $result[] = $current->value;
            }
            $current = $current->next;
            $index++;
        }
        
        return self::of(...$result);
    }

    /**
     * Finds first item in LinkedList by given predicate
     *
     * Complexity: O(n) - stops when predicate matches
     *
     * @param callable(T, int): bool $predicate
     *
     * @return Option<T>
     */
    public function find(callable $predicate): Option
    {
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            if ($predicate($current->value, $index)) {
                return Option::some($current->value);
            }
            $current = $current->next;
            $index++;
        }
        
        return Option::none();
    }

    /**
     * Finds first key in LinkedList by given predicate
     *
     * Complexity: O(n) - stops when predicate matches
     *
     * @param callable(T, int): bool $predicate
     *
     * @return Option<int>
     */
    public function findKey(callable $predicate): Option
    {
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            if ($predicate($current->value, $index)) {
                /** @var Option<int> $result */
                $result = Option::some($index);
                return $result;
            }
            $current = $current->next;
            $index++;
        }
        
        return Option::none();
    }

    /**
     * Checks if AT LEAST ONE item satisfies predicate
     *
     * Complexity: O(n) - stops when predicate matches
     *
     * @param callable(T, int): bool $predicate
     *
     * @return bool
     */
    public function exists(callable $predicate): bool
    {
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            if ($predicate($current->value, $index)) {
                return true;
            }
            $current = $current->next;
            $index++;
        }
        
        return false;
    }

    /**
     * Checks if given item is present in LinkedList
     *
     * Complexity: O(n)
     *
     * @param T $item
     * @param bool|null $strictComparison
     *
     * @return bool
     */
    public function contains($item, ?bool $strictComparison = true): bool
    {
        $current = $this->head;
        
        while ($current !== null) {
            if ($strictComparison && $current->value === $item) {
                return true;
            }
            
            if (!$strictComparison && $current->value === $item) {
                return true;
            }
            $current = $current->next;
        }
        
        return false;
    }

    /**
     * Checks if ALL items satisfy predicate
     *
     * Complexity: O(n) - stops immediately when some items don't satisfy predicate
     *
     * @param callable(T, int): bool $predicate
     *
     * @return bool
     */
    public function all(callable $predicate): bool
    {
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            if (!$predicate($current->value, $index)) {
                return false;
            }
            $current = $current->next;
            $index++;
        }
        
        return true;
    }

    /**
     * Reduce LinkedList to single value by applying reducer on each item
     *
     * Complexity: O(n)
     *
     * @template R
     *
     * @param callable(R, T, int): R $reducer
     * @param R $initialReduction
     *
     * @return R
     */
    public function reduce(callable $reducer, $initialReduction)
    {
        $carry = $initialReduction;
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            $carry = $reducer($carry, $current->value, $index);
            $current = $current->next;
            $index++;
        }
        
        return $carry;
    }

    /**
     * Reduce LinkedList to single value using Monoid
     *
     * Complexity: O(n)
     *
     * @param Monoid<T> $monoid
     *
     * @return T
     */
    public function mfold(Monoid $monoid)
    {
        if ($this->isEmpty()) {
            return $monoid->getEmpty();
        }
        
        $current = $this->head;
        if ($current === null) {
            return $monoid->getEmpty();
        }
        $result = $current->value;
        $current = $current->next;
        
        while ($current !== null) {
            $result = $monoid->concat($result, $current->value);
            $current = $current->next;
        }
        
        return $result;
    }

    /**
     * Converts items to numbers and then sums them up
     *
     * Complexity: O(n)
     *
     * @param callable(T): (int|float) $itemToNumber
     *
     * @return int|float
     */
    public function sum(callable $itemToNumber)
    {
        return $this->reduce(static fn ($carry, $next) => $carry + $itemToNumber($next), 0);
    }

    /**
     * Finds minimal value defined by comparator
     *
     * Complexity: O(n)
     *
     * @param null|callable(T, T): int $comparator
     *
     * @return Option<T>
     */
    public function min(?callable $comparator = null): Option
    {
        if ($this->isEmpty()) {
            return Option::none();
        }

        $comparator = $comparator ?? comparator();
        $current = $this->head;
        if ($current === null) {
            return Option::none();
        }
        $min = $current->value;
        $current = $current->next;
        
        while ($current !== null) {
            if ($comparator($min, $current->value) > 0) {
                $min = $current->value;
            }
            $current = $current->next;
        }

        return Option::some($min);
    }

    /**
     * Finds maximal value defined by comparator
     *
     * Complexity: O(n)
     *
     * @param null|callable(T, T): int $comparator
     *
     * @return Option<T>
     */
    public function max(?callable $comparator = null): Option
    {
        if ($this->isEmpty()) {
            return Option::none();
        }

        $comparator = $comparator ?? comparator();
        $current = $this->head;
        if ($current === null) {
            return Option::none();
        }
        $max = $current->value;
        $current = $current->next;
        
        while ($current !== null) {
            if ($comparator($max, $current->value) < 0) {
                $max = $current->value;
            }
            $current = $current->next;
        }

        return Option::some($max);
    }

    /**
     * Executes side effect on each item of LinkedList
     *
     * Complexity: O(n)
     *
     * @param callable(T, int): void $sideEffect
     *
     * @return void
     */
    public function each(callable $sideEffect): void
    {
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            $sideEffect($current->value, $index);
            $current = $current->next;
            $index++;
        }
    }

    /**
     * Executes side effect on each item and returns unchanged list
     *
     * Complexity: O(n)
     *
     * @param callable(T, int): void $sideEffect
     *
     * @return static<T>
     */
    public function tap(callable $sideEffect): self
    {
        $this->each($sideEffect);
        return $this;
    }

    /**
     * Gets the very first value of LinkedList
     *
     * Complexity: O(1)
     *
     * @return Option<T>
     */
    public function head(): Option
    {
        if ($this->head !== null) {
            return Option::some($this->head->value);
        }
        
        return Option::none();
    }

    /**
     * Gets the very last value of LinkedList
     *
     * Complexity: O(n)
     *
     * @return Option<T>
     */
    public function last(): Option
    {
        if ($this->isEmpty()) {
            return Option::none();
        }

        $current = $this->head;
        if ($current === null) {
            return Option::none();
        }
        while ($current->next !== null) {
            $current = $current->next;
        }

        return Option::some($current->value);
    }

    /**
     * Creates a copy of all nodes in the linked list
     *
     * Complexity: O(n)
     *
     * @return Node<T>|null
     */
    private function copyNodes(): ?Node
    {
        if ($this->head === null) {
            return null;
        }
        
        $newHead = new Node($this->head->value, null);
        $current = $this->head->next;
        $newCurrent = $newHead;
        
        while ($current !== null) {
            $newCurrent->next = new Node($current->value, null);
            $newCurrent = $newCurrent->next;
            $current = $current->next;
        }
        
        return $newHead;
    }

    /**
     * Finds the tail node of a copied linked list
     *
     * Complexity: O(n)
     *
     * @param Node<T> $head
     *
     * @return Node<T>
     */
    private function findTailOfCopy(Node $head): Node
    {
        $current = $head;
        while ($current->next !== null) {
            $current = $current->next;
        }
        return $current;
    }

    /**
     * Takes specified number of items from start
     *
     * Complexity: O(n) - where n is $size
     *
     * @param int $size
     *
     * @return static<T>
     */
    public function take(int $size): self
    {
        if ($size <= 0) {
            return self::fromEmpty();
        }
        
        if ($size >= $this->size) {
            return $this;
        }
        
        $result = [];
        $current = $this->head;
        $count = 0;
        
        while ($current !== null && $count < $size) {
            $result[] = $current->value;
            $current = $current->next;
            $count++;
        }
        
        return self::of(...$result);
    }

    /**
     * Gets sublist from original LinkedList
     *
     * Complexity: O(n) - where n is size of resulting list
     *
     * @param int $offset
     * @param int|null $limit
     *
     * @return static<T>
     */
    public function slice(int $offset, ?int $limit = null): self
    {
        if ($offset >= $this->size || $offset < 0) {
            return self::fromEmpty();
        }
        
        $result = [];
        $current = $this->head;
        $index = 0;
        
        // Skip to offset
        while ($current !== null && $index < $offset) {
            $current = $current->next;
            $index++;
        }
        
        // Collect items up to limit
        $count = 0;
        while ($current !== null && ($limit === null || $count < $limit)) {
            $result[] = $current->value;
            $current = $current->next;
            $count++;
        }
        
        return self::of(...$result);
    }

    /**
     * Creates a new LinkedList with null items filtered out
     *
     * Complexity: O(n)
     *
     * @return static<T>
     */
    public function withoutNulls(): self
    {
        return $this->filter(static fn ($item): bool => $item !== null);
    }

    /**
     * O(1) prepend operation - adds items to the front of the list
     * This is the most efficient operation for immutable linked lists
     *
     * Complexity: O(1) - just creates new head nodes
     *
     * @template T2
     *
     * @param T2 ...$items
     *
     * @return static<T|T2>
     */
    public function prepend(...$items): self
    {
        if (count($items) === 0) {
            return $this;
        }
        
        // Build the new head by prepending items in reverse order
        $newHead = $this->head;
        $newSize = $this->size;
        
        for ($i = count($items) - 1; $i >= 0; $i--) {
            /** @var Node<T|T2> $newNode */
            $newNode = new Node($items[$i], $newHead);
            /** @var Node<T|T2>|null $newHead */
            $newHead = $newNode;
            $newSize++;
        }
        
        return new static($newHead, $newSize);
    }

    /**
     * Efficient concatenation using structural sharing where possible
     * For LinkedList instances: O(1) by sharing the second list's structure
     * For other iterables: O(n) but more efficient than array conversion
     *
     * @template T2
     *
     * @param iterable<T2> $itemsToAdd
     *
     * @return static<T|T2>
     */
    public function concat(iterable $itemsToAdd)
    {
        if ($this->isEmpty()) {
            return self::fromIterable($itemsToAdd);
        }
        
        // Handle LinkedList instances with O(1) structural sharing
        if ($itemsToAdd instanceof self) {
            if ($itemsToAdd->isEmpty()) {
                return $this;
            }
            
            // O(1) concatenation: create new list that shares the second list's structure
            // This maintains immutability while being highly efficient
            $newHead = $this->copyNodes();
            if ($newHead === null) {
                return self::fromEmpty();
            }
            
            // Find the tail and link to the second list's head
            $tail = $this->findTailOfCopy($newHead);
            $tail->next = $itemsToAdd->head;
            
            /** @var static<T|T2> $result */
            $result = new static($newHead, $this->size + $itemsToAdd->size);
            return $result;
        }
        
        // For other iterables, use efficient append approach
        $items = self::convertIterableToArray($itemsToAdd);
        if (count($items) === 0) {
            return $this;
        }
        
        // Build the new list efficiently by copying current nodes and appending new ones
        $newHead = $this->copyNodes();
        if ($newHead === null) {
            return self::fromEmpty();
        }
        
        $tail = $this->findTailOfCopy($newHead);
        
        // Add new items to the tail
        for ($i = 0; $i < count($items); $i++) {
            $tail->next = new Node($items[$i], null);
            $tail = $tail->next;
        }
        
        /** @var static<T|T2> $castedResult */
        $castedResult = new static($newHead, $this->size + count($items));
        return $castedResult;
    }

    /**
     * Appends items to LinkedList
     *
     * @template T2
     *
     * @param T2 ...$items
     *
     * @return static<T|T2>
     */
    public function add(...$items): self
    {
        return $this->concat($items);
    }

    /**
     * Creates a LinkedList with reversed item order
     *
     * Complexity: O(n)
     *
     * @return static<T>
     */
    public function reverse(): self
    {
        $result = [];
        $current = $this->head;
        
        while ($current !== null) {
            $result[] = $current->value;
            $current = $current->next;
        }
        
        return self::of(...array_reverse($result));
    }

    /**
     * Gets classic php native array for interoperability
     *
     * Complexity: O(n)
     *
     * @return array<T>
     */
    public function toArray(): array
    {
        $result = [];
        $current = $this->head;
        
        while ($current !== null) {
            $result[] = $current->value;
            $current = $current->next;
        }
        
        return $result;
    }

    /**
     * Converts LinkedList to php native array for JSON serialization
     *
     * @return array<T>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Converts LinkedList to ArrayList
     *
     * @return ArrayList<T>
     */
    public function toList(): ArrayList
    {
        return ArrayList::fromIterable($this->toArray());
    }

    /**
     * Creates string by imploding all items with glue
     *
     * Complexity: O(n)
     *
     * @param string $glue
     *
     * @return string
     */
    public function join(string $glue): string
    {
        return implode($glue, $this->toArray());
    }

    /**
     * Converts LinkedList to LazyList
     *
     * @return LazyList<T>
     */
    public function lazy(): LazyList
    {
        return LazyList::fromIterable($this->toArray());
    }

    /**
     * A helper method for converting item to human readable string
     *
     * @internal
     *
     * @param T $item
     *
     * @return string
     */
    private function itemToString($item): string
    {
        switch (true) {
            case is_array($item):
                return '[' . ArrayList::fromIterable($item)
                    ->map(fn($v, $k) => sprintf('%s => %s', $k, $this->itemToString($v)))
                    ->join(', ')
                . ']';
            case !is_object($item):
            case method_exists($item, '__toString'):
                return (string) $item;
            default:
                return sprintf('(%s) %s', $item::class, spl_object_hash($item));
        }
    }

    /**
     * Classic toString method, mainly for debugging purposes
     *
     * Complexity: O(n)
     */
    public function __toString(): string
    {
        return '[' . $this
                ->map(fn ($item): string => $this->itemToString($item))
                ->join(', ') . ']';
    }
}
