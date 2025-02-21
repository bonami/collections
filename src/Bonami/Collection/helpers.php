<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Hash\IHashable;

/**
 * @template A
 *
 * @param A $x
 *
 * @return A
 */
function id($x)
{
    return $x;
}

/** @deprecated use id with function reference instead */
function identity(): callable
{
    return id(...);
}

function comparator(): callable
{
    return static fn ($a, $b): int => $a <=> $b;
}

function descendingComparator(): callable
{
    return static fn ($a, $b): int => $b <=> $a;
}

function tautology(): callable
{
    return static fn (): bool => true;
}

function falsy(): callable
{
    return static fn (): bool => false;
}

/**
 * Returns function that supplies $args as an arguments to passed function
 *
 * @template A
 *
 * @param A $arg $args
 *
 * @return callable(callable(A): mixed): mixed
 */
function applicator1($arg): callable
{
    return static fn (callable $callable) => $callable($arg);
}

/**
 * @template A
 * @template B
 * @template C
 *
 * @param callable(B): C $f
 * @param callable(A): B $g
 *
 * @return callable(A): C
 */
function compose(callable $f, callable $g): callable
{
    return static fn (...$args) => $f($g(...$args));
}

function hashKey(mixed $key): int|string
{
    if ($key === (object)$key) {
        return $key instanceof IHashable
            ? $key->hashCode()
            : spl_object_hash($key);
    }
    if (is_array($key)) {
         return serialize(array_map(static fn ($value) => hashKey($value), $key));
    }
    if (is_null($key)) {
         return "";
    }
    if (is_bool($key)) {
         return (int)$key;
    }

    return $key;
}
