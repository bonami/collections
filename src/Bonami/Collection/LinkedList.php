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
use RuntimeException;
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
        if ($iterable instanceof self) {
            return $iterable;
        }

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
            $current = $current->getNext();
        }

        if ($current !== null) {
            return Option::some($current->getValue());
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
        /** @var Node<B>|null $newHead */
        $newHead = null;
        $newSize = 0;
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            /** @var B $mapped */
            $mapped = $mapper($current->getValue(), $index);
            $newHead = new Node($mapped, $newHead);
            $newSize++;
            $current = $current->getNext();
            $index++;
        }
        
        /** @var static<B> $result */
        $result = (new static($newHead, $newSize))->reverse();
        return $result;
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
        /** @var Node<B>|null $newHead */
        $newHead = null;
        $newSize = 0;
        $current = $this->head;
        $index = 0;

        while ($current !== null) {
            /** @var iterable<B> $mapped */
            $mapped = $mapper($current->getValue(), $index);
            foreach ($mapped as $item) {
                /** @var B $item */
                $newHead = new Node($item, $newHead);
                $newSize++;
            }
            $current = $current->getNext();
            $index++;
        }

        /** @var static<B> $result */
        $result = (new static($newHead, $newSize))->reverse();
        return $result;
    }

    /**
     * Flattens LinkedList of iterables into LinkedList
     *
     * Complexity: O(m) - where m is a number of resulting items (sum of nested array sizes)
     *
     * @return static<mixed>
     */
    public function flatten(): self
    {
        // Build the LinkedList in reverse order (for efficient prepend)
        $newHead = null;
        $newSize = 0;
        $current = $this->head;
        
        while ($current !== null) {
            $nested = $current->getValue();
            if (!is_iterable($nested)) {
                throw new RuntimeException('Some item cannot be flattened because it is not iterable');
            }

            foreach ($nested as $item) {
                $newHead = new Node($item, $newHead);
                $newSize++;
            }
            $current = $current->getNext();
        }
        
        // Reverse to get correct order
        return (new static($newHead, $newSize))->reverse();
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
        /** @var Node<T>|null $newHead */
        $newHead = null;
        $newSize = 0;
        $current = $this->head;
        $index = 0;
        
        while ($current !== null) {
            if ($predicate($current->getValue(), $index)) {
                $newHead = new Node($current->getValue(), $newHead);
                $newSize++;
            }
            $current = $current->getNext();
            $index++;
        }
        
        /** @var static<T> $result */
        $result = (new static($newHead, $newSize))->reverse();
        return $result;
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
            if ($predicate($current->getValue(), $index)) {
                return Option::some($current->getValue());
            }
            $current = $current->getNext();
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
            if ($predicate($current->getValue(), $index)) {
                /** @var Option<int> $result */
                $result = Option::some($index);
                return $result;
            }
            $current = $current->getNext();
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
            if ($predicate($current->getValue(), $index)) {
                return true;
            }
            $current = $current->getNext();
            $index++;
        }
        
        return false;
    }

    /**
     * Checks if given item is present in LinkedList
     *
     * Complexity: O(n)
     *
     * @template A extends T
     *
     * @param A $item
     * @param bool|null $strictComparison
     *
     * @return bool
     */
    public function contains($item, ?bool $strictComparison = true): bool
    {
        $current = $this->head;
        $useStrict = $strictComparison ?? true;
        
        if ($useStrict) {
            while ($current !== null) {
                if ($current->getValue() === $item) {
                    return true;
                }
                $current = $current->getNext();
            }
            return false;
        }
        
        while ($current !== null) {
            // non-strict comparison
            // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators
            if ($current->getValue() == $item) {
                return true;
            }
            $current = $current->getNext();
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
            if (!$predicate($current->getValue(), $index)) {
                return false;
            }
            $current = $current->getNext();
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
            $carry = $reducer($carry, $current->getValue(), $index);
            $current = $current->getNext();
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
        $result = $current->getValue();
        $current = $current->getNext();
        
        while ($current !== null) {
            $result = $monoid->concat($result, $current->getValue());
            $current = $current->getNext();
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
        $min = $current->getValue();
        $current = $current->getNext();
        
        while ($current !== null) {
            if ($comparator($min, $current->getValue()) > 0) {
                $min = $current->getValue();
            }
            $current = $current->getNext();
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
        $max = $current->getValue();
        $current = $current->getNext();
        
        while ($current !== null) {
            if ($comparator($max, $current->getValue()) < 0) {
                $max = $current->getValue();
            }
            $current = $current->getNext();
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
            $sideEffect($current->getValue(), $index);
            $current = $current->getNext();
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
            return Option::some($this->head->getValue());
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
        while ($current->getNext() !== null) {
            $current = $current->getNext();
        }

        return Option::some($current->getValue());
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
        
        $newHead = new Node($this->head->getValue(), null);
        $current = $this->head->getNext();
        $newCurrent = $newHead;
        
        while ($current !== null) {
            $newCurrent->setNext(new Node($current->getValue(), null));
            $newCurrent = $newCurrent->getNext();
            if ($newCurrent === null) {
                break;
            }
            $current = $current->getNext();
        }
        
        return $newHead;
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
        
        $current = $this->head;
        $count = 0;
        /** @var Node<T>|null $newHead */
        $newHead = null;
        
        while ($current !== null && $count < $size) {
            $newHead = new Node($current->getValue(), $newHead);
            $current = $current->getNext();
            $count++;
        }
        
        /** @var static<T> $result */
        $result = new static($newHead, $count);
        return $result->reverse();
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
        
        $current = $this->head;
        $index = 0;
        
        // Skip to offset
        while ($current !== null && $index < $offset) {
            $current = $current->getNext();
            $index++;
        }
        
        // Collect items up to limit (or to the end when limit is null)
        /** @var Node<T>|null $newHead */
        $newHead = null;
        $newSize = 0;
        
        while ($current !== null && ($limit === null || $newSize < $limit)) {
            $newHead = new Node($current->getValue(), $newHead);
            $newSize++;
            $current = $current->getNext();
        }
        
        /** @var static<T> $result */
        $result = (new static($newHead, $newSize))->reverse();
        return $result;
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
        /** @var Node<T|T2>|null $newHead */
        $newHead = $this->head;
        $newSize = $this->size;
        
        for ($i = count($items) - 1; $i >= 0; $i--) {
            $item = $items[$i];
            $newHead = new Node($item, $newHead);
            $newSize++;
        }
        
        return new static($newHead, $newSize);
    }

    /**
     * Efficient concatenation using structural sharing where possible
     *
     * Complexity:
     * - For LinkedList instances: O(m) + O(1) by copying first list (m nodes of first list) and sharing second list's structure (constant time)
     * - For other iterables: O(n)
     *
     * @template T2
     *
     * @param iterable<T2> $itemsToAdd
     *
     * @return static<T|T2>
     */
    public function concat(iterable $itemsToAdd): self
    {
        if ($itemsToAdd instanceof Countable && $itemsToAdd->count() === 0) {
            return $this;
        }
        
        /** @var static<T|T2> $items */
        $items = self::fromIterable($itemsToAdd);
        if ($this->isEmpty()) {
            return $items;
        }
        
        /** @var Node<T|T2>|null $newHead */
        $newHead = $this->copyNodes();
        assert($newHead !== null); // Cannot be null, because it is not empty
        
        // Find the tail and link to the second list's head
        $tail = $newHead->findTail();
        $tail->setNext($items->head);

        return new static($newHead, $this->size + $items->size);
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
        $current = $this->head;
        $newHead = null;
        
        while ($current !== null) {
            $newHead = new Node($current->getValue(), $newHead);
            $current = $current->getNext();
        }
        
        return new static($newHead, $this->size);
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
            $result[] = $current->getValue();
            $current = $current->getNext();
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
                ->join(' -> ') . ']';
    }
}
