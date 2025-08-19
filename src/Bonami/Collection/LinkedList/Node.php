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
    public $value;

    /** @var Node<T>|null */
    public $next;

    /**
     * @param T $value
     * @param Node<T>|null $next
     */
    public function __construct($value, ?Node $next)
    {
        $this->value = $value;
        $this->next = $next;
    }
}
