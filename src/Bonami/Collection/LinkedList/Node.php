<?php

declare(strict_types=1);

namespace Bonami\Collection\LinkedList;

/**
 * @template T
 *
 * Internal node class for LinkedList
 */
final class Node
{
    /** @var T */
    private $value;

    /** @var Node<T>|null */
    private $next;

    /**
     * @param T $value
     * @param Node<T>|null $next
     */
    public function __construct($value, ?Node $next)
    {
        $this->value = $value;
        $this->next = $next;
    }

    /** @return T */
    public function getValue()
    {
        return $this->value;
    }

    /** @return Node<T>|null */
    public function getNext(): ?Node
    {
        return $this->next;
    }

    /** @param Node<T>|null $next */
    public function setNext(?Node $next): void
    {
        $this->next = $next;
    }

    /**
     * Complexity: O(n) - where n is the number of nodes in the list
     *
     * @return Node<T>
     */
    public function findTail(): Node
    {
        $current = $this;
        while ($current->next !== null) {
            $current = $current->next;
        }
        return $current;
    }
}
