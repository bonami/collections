<?php declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Hash\IHashable;

function identity(): callable {
	return static function ($argument) { return $argument; };
}

function comparator(): callable {
	return static function ($a, $b): int { return $a <=> $b; };
}

function tautology(): callable {
	return static function (): bool { return true; };
}

/**
 * Returns function that supplies $args as an arguments to passed function
 */
function applicator(...$args): callable {
	return function (callable $callable) use ($args) {
		return $callable(...$args);
	};
}

function compose(callable $f, callable $g): callable {
	return function(...$args) use ($f, $g) {
		return $f($g(...$args));
	};
}

function hashKey($key) {
	if ($key === (object)$key) {
		if ($key instanceof IHashable) {
			return $key->hashCode() ?? spl_object_hash($key);
		}

		return spl_object_hash($key);
	}
	if (is_array($key)) {
		return serialize(array_map(function ($value) {
			return hashKey($value);
		}, $key));
	}
	if (is_bool($key)) {
		return (int)$key;
	}

	return $key;
}
