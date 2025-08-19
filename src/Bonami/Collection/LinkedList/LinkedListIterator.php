<?php

declare(strict_types=1);

namespace Bonami\Collection\LinkedList;

use Iterator;

/**
 * @template T
 *
 * @implements Iterator<int, T>
 */
final class LinkedListIterator implements Iterator
{
    /** @var Node<T>|null */
    private $current;

    /** @var int */
    private $index;

    /** @var Node<T>|null */
    private $head;

    /** @param Node<T>|null $head */
    public function __construct(?Node $head)
    {
        $this->head = $head;
        $this->current = $head;
        $this->index = 0;
    }

    public function current(): mixed
    {
        if ($this->current === null) {
            throw new \RuntimeException('Iterator is not valid');
        }
        
        return $this->current->getValue();
    }

    public function key(): int
    {
        return $this->index;
    }

    public function next(): void
    {
        if ($this->current !== null) {
            $this->current = $this->current->getNext();
            $this->index++;
        }
    }

    public function rewind(): void
    {
        $this->current = $this->head;
        $this->index = 0;
    }

    public function valid(): bool
    {
        return $this->current !== null;
    }
}
