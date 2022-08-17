<?php

declare(strict_types=1);

namespace Bonami\Collection;

use Bonami\Collection\Exception\OutOfBoundsException;
use Bonami\Collection\Hash\IHashable;
use Countable;
use Iterator;
use IteratorAggregate;
use ReflectionClass;
use Traversable;

use function array_chunk;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_reduce;
use function array_replace;
use function array_values;
use function count;
use function get_class;
use function in_array;
use function is_array;
use function is_scalar;
use function iterator_to_array;

/**
 * @template K
 * @template V
 *
 * @implements IteratorAggregate<K, V>
 */
class Map implements Countable, IteratorAggregate
{
    /** @var array<int|string, K> */
    protected $keys;

    /** @var array<int|string, V> */
    protected $values;

    /** @param array<int, array{0: K, 1: V}> $items */
    final public function __construct(array $items)
    {
        $this->keys = [];
        $this->values = [];

        foreach ($items as [$key, $value]) {
            $keyHash = hashKey($key);
            $this->keys[$keyHash] = $key;
            $this->values[$keyHash] = $value;
        }
    }

    /**
     * Creates Map from associative array.
     *
     * This takes keys from given array
     * and uses it as keys for constructed Map.
     *
     * Complexity: o(n)
     *
     * @see fromIterable if you need to create Map from array of pairs (2-dimensional array)
     *
     * @template A of int|string
     * @template B
     *
     * @param array<A, B> $array
     *
     * @return static<A, B>
     */
    public static function fromAssociativeArray(array $array)
    {
        return new static(array_map(null, array_keys($array), $array));
    }

    /**
     * Creates a single element Map with $key $value pair
     *
     * Complexity: o(1)
     *
     * @template A
     * @template B
     *
     * @param A $key
     * @param B $value
     *
     * @return static<A, B>
     */
    public static function fromOnly($key, $value)
    {
        return new static([[$key, $value]]);
    }

    /**
     * Gets value by key wrapped with Option::some if exists and Option::none
     * otherwise.
     *
     * Complexity: o(1)
     *
     * @see getOrElse for getting default value in case the key does not exists
     *
     * @param K $key
     *
     * @return Option<V>
     */
    public function get($key): Option
    {
        $keyHash = hashKey($key);
        if (array_key_exists($keyHash, $this->values)) {
            return Option::some($this->values[$keyHash]);
        }
        return Option::none();
    }

    /**
     * Gets value by key if exists and throws and exception if it does not.
     * Use this exception if it is guaranteed, that the key is defined (or you
     * want to fail when it does not)
     *
     * Complexity: o(1)
     *
     * @throws OutOfBoundsException
     *
     * @return V
     *
     * @see getOrElse for getting default value in case the key does not exists
     *
     * @param K $key
     *
     * @see get for getting value the safe way
     */
    public function getUnsafe($key)
    {
        $keyHash = hashKey($key);
        if (array_key_exists($keyHash, $this->values)) {
            return $this->values[$keyHash];
        }

        switch (true) {
            case is_scalar($key):
                $stringKey = $key;
                break;
            case $key instanceof Enum:
                $stringKey = get_class($key) . '::' . $key->getValue();
                break;
            case $key instanceof IHashable:
                $stringKey = get_class($key) . ' keyhash:' . $keyHash;
                break;
            case is_object($key) && (new ReflectionClass($key))->hasMethod('__toString'):
                $stringKey = (string)$key;
                break;
            case is_object($key):
                $stringKey = get_class($key);
                break;
            default:
                $stringKey = 'unknown';
        }

        throw new OutOfBoundsException(sprintf('Key (%s) does not exist', $stringKey));
    }

    /**
     * Gets all values from the Map and wraps it into ArrayList
     *
     * Complexity: o(n)
     *
     * @return ArrayList<V>
     */
    public function values(): ArrayList
    {
        return ArrayList::fromIterable(array_values($this->values));
    }

    /**
     * Gets value by $key if the $key is defined and $defaultValue otherwise
     *
     * Complexity: o(1)
     *
     * @see get for getting value the safe way when no default value is suitable
     *
     * @template E
     *
     * @param K $key
     * @param E $defaultValue
     *
     * @return V|E
     */
    public function getOrElse($key, $defaultValue)
    {
        $keyHash = hashKey($key);
        if (array_key_exists($keyHash, $this->values)) {
            return $this->values[$keyHash];
        }
        return $defaultValue;
    }

    /**
     * Method from IteratorAggregate interface allowing foreach operation on Map.
     * Internally implemented as Generator with yield operation, which allows safely
     * iterate even over object keys.
     *
     * The underlying implementation can change in future, so the client code should
     * not depend on information that it is implemented as Generator.
     *
     * Only important information from this is that obtained iterator is not rewindable
     * once it was read (which is implied by internal via implementation yield). This
     * subject due to change.
     *
     * Complexity: o(1) - getting the iterator itself is o(1) because it uses yield internally.
     * Iterating over the iterator is of course o(n)
     *
     * @return Iterator<K, V>
     */
    public function getIterator(): Iterator
    {
        foreach ($this->values as $keyHash => $value) {
            yield $this->keys[$keyHash] => $value;
        }
    }

    /**
     * Checks if Map is empty
     *
     * Complexity: o(1)
     *
     * @see isNotEmpty
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Counts number of elements in Map
     *
     * Complexity: o(1)
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->values);
    }

    /**
     * Checks if Map is not empty
     *
     * Complexity: o(1)
     *
     * @see isEmpty
     *
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return $this->count() > 0;
    }

    /**
     * Checks if Map contains given $value. It uses strict comparison, which means
     * that two object instances are same only if the have same reference.
     *
     * Complexity: o(n)
     *
     * @see exists if you need to check value existance more losely other then strict comparison
     *
     * @param V $value
     * @param bool|null $strictComparison
     *
     * @return bool
     */
    public function contains($value, ?bool $strictComparison = true): bool
    {
        return in_array($value, $this->values, $strictComparison ?? true);
    }

    /**
     * Checks if $key is defined in Map.
     *
     * Complexity: o(1)
     *
     * @param K $key
     *
     * @return bool
     */
    public function has($key): bool
    {
        return array_key_exists(hashKey($key), $this->keys);
    }

    /**
     * Constructs a Map containing same values as original with mapped keys
     * by applying callback on each key-value pair from Map
     *
     * Complexity: o(n)
     *
     * @see map - for mapping both keys and values and returning ArrayList as result
     * @see mapValues - for mapping just values and keeping Map as result
     *
     * @template B
     *
     * @param callable(K, V): B $mapper
     *
     * @return self<B, V>
     */
    public function mapKeys(callable $mapper): self
    {
        /** @var self<B, V> $map */
        $map = self::fromEmpty();

        $keysValues = array_map($mapper, $this->keys, $this->values);
        $keyHashes = array_map(static function ($key) {
            return hashKey($key);
        }, $keysValues);

        $keys = array_combine($keyHashes, $keysValues);
        $values = array_combine($keyHashes, $this->values);
        assert(is_array($keys));
        assert(is_array($values));

        $map->keys = $keys;
        $map->values = $values;

        return $map;
    }

    /**
     * Creates an empty Map
     *
     * Complexity: o(1)
     *
     * @return static<K, V>
     */
    public static function fromEmpty()
    {
        return new static([]);
    }

    /**
     * Constructs a Map containing same keys as original with mapped values
     * by applying callback on each value-key pair from Map
     *
     * Complexity: o(n)
     *
     * @template B
     *
     * @see map - for mapping both keys and values and returning ArrayList as result
     * @see mapKeys - for mapping just keys and keeping Map as result
     *
     * @param callable(V, K): B $mapper
     *
     * @return self<K, B>
     */
    public function mapValues(callable $mapper): self
    {
        /** @var self<K, B> $map */
        $map = self::fromEmpty();
        $map->keys = $this->keys;
        $values = array_combine(
            array_keys($this->keys),
            array_map($mapper, $this->values, $this->keys)
        );
        assert(is_array($values));
        $map->values = $values;

        return $map;
    }

    /**
     * Converts map into associative array. It works well if keys are scalar values.
     *
     * Please note, that when applied on object keys, conversion to string is done. This should work well
     * when object implements __toString method.
     *
     * Please note, that when converted object keys into strings causes duplicate keys in resulting array,
     * then last value is used.
     *
     * Complexity: o(n)
     *
     * @return array<string, V>
     */
    public function toAssociativeArray(): array
    {
        /** @var array<string, V> */
        $assoc = array_combine(
            array_map(static function ($key) {
                return (string)$key;
            }, $this->keys),
            $this->values
        );
        assert(is_array($assoc));

        return $assoc;
    }

    /**
     * Adds all values from $mergeMap where keys are not defined yet
     * and replaces values from $mergeMap where keys are already defined
     *
     * Complexity: o(n + m)
     *   - n is number of items of original collection
     *   - m is number of items of given collection
     *
     * @template K2
     * @template V2
     *
     * @param Map<K2, V2> $mergeMap
     *
     * @return static<K|K2, V|V2>
     */
    public function concat(Map $mergeMap)
    {
        /** @var self<K|K2, V|V2> $map */
        $map = static::fromEmpty();
        $keys = array_replace($this->keys, $mergeMap->keys);
        $values = array_replace($this->values, $mergeMap->values);

        assert($keys !== null);
        assert($values !== null);

        $map->keys = $keys;
        $map->values = $values;
        return $map;
    }

    /**
     * Removes all items from original Map where both key and value from given
     * Map equals.
     *
     * For key equality is used internal hashKey method
     * For value equality is used strict comparison
     *
     * If other equality mechanism is needed, use combinations of other methods
     *
     * Complexity: o(n)
     *
     * @see filter - for filtering original map with predicate in both values and keys
     * @see filterKeys - for filtering original map with predicate in keys only
     * @see withoutKeys - for simply removing values by given keys no matter what the value is
     *
     * @param Map<K, V> $map
     *
     * @return static<K, V>
     */
    public function minus(Map $map)
    {
        return $this->filter(static function ($value, $key) use ($map) {
            $keyHash = hashKey($key);
            return !(array_key_exists($keyHash, $map->values) && $map->values[$keyHash] === $value);
        });
    }

    /**
     * Filters out Map by given predicate executed on value-key pairs
     *
     * Complexity: o(n)
     *
     * @see filterKeys - if your predicate needs just keys
     *
     * @param callable(V, K): bool $predicate
     *
     * @return static<K, V>
     */
    public function filter(callable $predicate)
    {
        $filtered = [];
        foreach ($this->values as $keyHash => $value) {
            $key = $this->keys[$keyHash];
            if ($predicate($value, $key)) {
                $filtered[] = [$key, $value];
            }
        }
        return new static($filtered);
    }

    /**
     * Apply callback to each element. This method is designed for passing callbacks with side
     * effects (since it does not mutate and does not return anything, callback without side effect would
     * not have any meaning)
     *
     * Complexity: o(n)
     *
     * @param callable(V, K): void $sideEffect
     *
     * @return void
     */
    public function each(callable $sideEffect): void
    {
        foreach ($this->values as $keyHash => $value) {
            $sideEffect($value, $this->keys[$keyHash]);
        }
    }

    /**
     * Executes $sideEffect on each value-key pair of Map and returns unchanged Map.
     *
     * Allows inserting side-effects in a chain of method calls
     *
     * Complexity: o(n)
     *
     * @param callable(V, K): void $sideEffect
     *
     * @return static<K, V>
     */
    public function tap(callable $sideEffect)
    {
        foreach ($this->values as $keyHash => $value) {
            $sideEffect($value, $this->keys[$keyHash]);
        }

        return $this;
    }

    /**
     * Filters out Map by given predicate executed on keys
     *
     * Complexity: o(n)
     *
     * @see filter - if your predicate needs values too
     *
     * @param callable(K): bool $predicate
     *
     * @return static<K, V>
     */
    public function filterKeys(callable $predicate)
    {
        $filtered = [];
        foreach ($this->keys as $keyHash => $key) {
            if ($predicate($key)) {
                $filtered[] = [$key, $this->values[$keyHash]];
            }
        }
        return new static($filtered);
    }

    /**
     * Finds first value in Map by given predicate where it returns true for value-key pair
     *
     * Complexity: o(n) - stops when predicate matches
     *
     * @see exists - if you just need to check if some pair matches given predicate
     *
     * @param callable(V, K): bool $predicate
     *
     * @return Option<V>
     */
    public function find(callable $predicate): Option
    {
        foreach ($this->values as $keyHash => $value) {
            if ($predicate($value, $this->keys[$keyHash])) {
                return Option::some($value);
            }
        }
        return Option::none();
    }

    /**
     * Finds first key in Map by given predicate where it returns true for value-key pair
     *
     * Complexity: o(n) - stops when predicate matches
     *
     * @param callable(K, V): bool $predicate
     *
     * @return Option<K>
     */
    public function findKey(callable $predicate): Option
    {
        foreach ($this->keys as $keyHash => $key) {
            if ($predicate($key, $this->values[$keyHash])) {
                return Option::some($key);
            }
        }
        return Option::none();
    }

    /**
     * Takes specified number of items from start
     *
     * Complexity: o(n) - where n is `$size`
     *
     * @return static<K, V>
     */
    public function take(int $size)
    {
        /** @var array<int, array{0: K, 1: V}> */
        $zipped = array_map(
            null,
            array_slice($this->keys, 0, $size, true),
            array_slice($this->values, 0, $size, true)
        );

        return static::fromIterable($zipped);
    }

    /**
     * Creates Map from collection of tuples,
     * for example list of two element arrays.
     *
     * Complexity: o(n) - there are different hidden constants cost varying on given iterable.
     *
     * @template A
     * @template B
     *
     * @param Map<A, B>|iterable<array{0: A, 1: B}> $iterable
     *
     * @return static<A, B>
     */
    public static function fromIterable(iterable $iterable)
    {
        if ($iterable instanceof static) {
            $map = new static([]);
            $map->keys = $iterable->keys;
            $map->values = $iterable->values;

            return $map;
        }

        if ($iterable instanceof self) {
            return new static($iterable->getItems());
        }

        if ($iterable instanceof ArrayList) {
            return new static($iterable->toArray());
        }

        return new static($iterable instanceof Traversable ? iterator_to_array($iterable, false) : $iterable);
    }

    /**
     * Zips keys and values and returns 2-dimensional array of key-value pairs.
     * Result should be same as array with which this exact map can be constructed
     * via standard constructor.
     *
     * Complexity: o(n)
     *
     * @return array<int, array{0: K, 1: V}>
     */
    public function getItems(): array
    {
        /** @var array<int, array{0: K, 1: V}> */
        $zipped = array_map(null, $this->keys, $this->values);

        return $zipped;
    }

    /**
     * Returns true when first value-key pair matches predicate, false otherwise.
     *
     * Complexity: o(n) - stops when predicate matches
     *
     * @see find - if you just need to find concreate value-key pair that satisfies given predicate
     *
     * @param callable(V, K): bool $predicate
     *
     * @return bool
     */
    public function exists(callable $predicate): bool
    {
        foreach ($this->values as $keyHash => $value) {
            if ($predicate($value, $this->keys[$keyHash])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns true when all value-key pairs match predicate, false otherwise.
     *
     * Complexity: o(n) - stops immediately when predicate does not match
     *
     * @param callable(V, K): bool $predicate
     *
     * @return bool
     */
    public function all(callable $predicate): bool
    {
        foreach ($this->values as $keyHash => $value) {
            if (!$predicate($value, $this->keys[$keyHash])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Creates a copied Map without pairs, where value equals null
     *
     * Complexity: o(n)
     *
     * @return static<K, V>
     */
    public function withoutNulls()
    {
        return $this->filter(static function ($item, $_): bool {
            return $item !== null;
        });
    }

    /**
     * Creates a Map without pairs which has given keys. Missing keys in
     * original Map are silently skipped (no error is thrown)
     *
     * Complexity: o(n)
     *
     * @param iterable<K> $keys
     *
     * @return static<K, V>
     */
    public function withoutKeys(iterable $keys)
    {

        $hashes = ArrayList::fromIterable(array_keys($this->keys))
            ->minus(LazyList::fromIterable($keys)->map(static function ($key) {
                return hashKey($key);
            }));

        $hashesArray = $hashes->toArray();
        $newKeys = array_combine($hashesArray, $hashes->map(function ($keyHash) {
            return $this->keys[$keyHash];
        })->toArray());
        $newValues = array_combine($hashesArray, $hashes->map(function ($keyHash) {
            return $this->values[$keyHash];
        })->toArray());
        assert(is_array($newKeys));
        assert(is_array($newValues));

        $map = static::fromEmpty();
        $map->keys = $newKeys;
        $map->values = $newValues;

        return $map;
    }

    /**
     * Creates a Map without pair for given key. If the key is missing in
     * original Map (no error is thrown) and unchanges Map is returned.
     *
     * Complexity: o(n)
     *
     * @param K $key
     *
     * @return static<K, V>
     */
    public function withoutKey($key)
    {
        $keyHash = hashKey($key);
        if (!array_key_exists($keyHash, $this->keys)) {
            return $this;
        }

        $keys = $this->keys;
        $values = $this->values;
        unset($keys[$keyHash], $values[$keyHash]);

        $map = static::fromEmpty();
        $map->keys = $keys;
        $map->values = $values;

        return $map;
    }

    /**
     * Creates a Map containing all pairs with sorted keys
     * by given comparison callback
     *
     * Complexity: o(n*log(n))
     *
     * @param callable(K, K):int|null $comparator - A standard comparator expecting two arguments
     *                                  returning values -1, 0 or 1. When no comparator is passed,
     *                                  standard <=> operator is used to between values.
     *
     * @return static<K, V>
     */
    public function sortKeys(?callable $comparator = null)
    {
        return $this->getByKeys($this->keys()->sort($comparator));
    }

    /**
     * Creates a Map containing pairs for given keys. Missing keys in
     * original Map are silently skipped (no error is thrown)
     *
     * As a by product, returned Map keys are ordered by given $keys
     *
     * Complexity: o(n)
     *
     * @param iterable<K> $keys
     *
     * @return static<K, V>
     */
    public function getByKeys(iterable $keys)
    {
        $hashes = ArrayList::fromIterable($keys)
            ->map(static function ($key) {
                return hashKey($key);
            })
            ->filter(function ($keyHash) {
                return array_key_exists($keyHash, $this->keys);
            });

        $hashesArray = $hashes->toArray();
        $newKeys = array_combine($hashesArray, $hashes->map(function ($keyHash) {
            return $this->keys[$keyHash];
        })->toArray());
        $newValues = array_combine($hashesArray, $hashes->map(function ($keyHash) {
            return $this->values[$keyHash];
        })->toArray());
        assert(is_array($newKeys));
        assert(is_array($newValues));

        $map = static::fromEmpty();
        $map->keys = $newKeys;
        $map->values = $newValues;

        return $map;
    }

    /**
     * Gets all keys from the Map and wraps it into ArrayList
     *
     * Complexity: o(n)
     *
     * @return ArrayList<K>
     */
    public function keys(): ArrayList
    {
        return ArrayList::fromIterable(array_values($this->keys));
    }

    /**
     * Creates a Map containing all pairs sorted by values
     * by given comparison callback
     *
     * Pairing between keys and values is kept
     *
     * Complexity: o(n*log(n))
     *
     * @param callable(V, V):int|null $comparator - A standard comparator expecting two arguments
     *                                  returning values -1, 0 or 1. When no comparator is passed,
     *                                  standard <=> operator is used to between values.
     *
     * @return static<K, V>
     */
    public function sortValues(?callable $comparator = null)
    {
        $comparator = $comparator ?? comparator();
        return static::fromIterable(
            $this->pairs()->sort(static function (array $a, array $b) use ($comparator) {
                return $comparator($a[1], $b[1]);
            })
        );
    }

    /**
     * Creates List containing key-value array pairs as items.
     *
     * Complexity: o(n)
     *
     * @return ArrayList<array{0: K, 1: V}>
     */
    public function pairs(): ArrayList
    {
        return ArrayList::fromIterable($this->getItems());
    }

    /**
     * Reduces Map value-key pairs into single product.
     *
     * Complexity: o(n)
     *
     * @template R
     *
     * @param callable(R, V, K): R $reducer - takes up to 3 parametrs and returns next reduction step:
     *                          (?prevReduction, ?currentValue, ?currentKey) => nextReduction
     *
     * @param R $initialReduction - an initial reduction used for the first reduction step as prevReduction.
     *                                It is also immediately returned if the collection is empty.
     *
     * @return R
     */
    public function reduce(callable $reducer, $initialReduction)
    {
        return array_reduce(array_keys($this->keys), function ($reduction, $keyHash) use ($reducer) {
            return $reducer($reduction, $this->values[$keyHash], $this->keys[$keyHash]);
        }, $initialReduction);
    }

    /**
     * Chunks the Map to smaller Maps in size of $size. Resulting Maps are wrapped into ArrayList.
     * If the $size is smaller then original Map size, single element ArrayList is returned containing
     * The same exactly same values as original map.
     *
     * If size of the original Map is not dividable by chunkSize, the last element of resulting ArrayList is Map
     * which contains less the chunkSize elements.
     *
     * Complexity: o(n)
     *
     * @param int<1, max> $size - A size of resulting Map chunk
     *
     * @return ArrayList<Map<K, V>> - a list of Map chunks of size $size
     */
    public function chunk(int $size): ArrayList
    {
        return LazyList::fromIterable(array_chunk($this->keys, $size, true))
            ->zip(array_chunk($this->values, $size, true))
            ->map(static function (array $zipped) {
                [$keysChunk, $valuesChunk] = $zipped;

                /** @var Map<K, V> $map */
                $map = static::fromEmpty();
                $map->keys = $keysChunk;
                $map->values = $valuesChunk;

                return $map;
            })
            ->toList();
    }

    /**
     * Classic toString method, mainly for debugging purposes.
     *
     * Complexity: o(n)
     */
    public function __toString(): string
    {
        return '{' . $this->map(static function ($value, $key): string {
            return sprintf('%s: %s', $key, $value);
        })->join(', ') . '}';
    }

    /**
     * Constructs a list containing all elements after applying callback
     * on each value-key pair from Map
     *
     * Complexity: o(n)
     *
     * @see mapValues - for mapping just values and keeping Map as result
     * @see mapKeys - for mapping just keys and keeping Map as result
     * @see flatMap - when you need to flatten the resulting list
     *
     * @template B
     *
     * @param callable(V, K): B $mapper
     *
     * @return ArrayList<B>
     */
    public function map(callable $mapper): ArrayList
    {
        /** @var array<int, B> $mapped */
        $mapped = array_map($mapper, $this->values, $this->keys);

        return ArrayList::fromIterable($mapped);
    }

    /**
     * This is not monadic flatMap operation!
     *
     * Constructs a list containing all elements after applying callback
     * on each value-key pair from Map and then flattens that list.
     *
     * It is equivalent of chaining $map->map($mapper)->flatten() except,
     * that this way we can do it more type safe.
     *
     * Complexity: o(n)
     *
     * @see mapValues - for mapping just values and keeping Map as result
     * @see mapKeys - for mapping just keys and keeping Map as result
     * @see map - for mapping key-value pairs when no flattenting is needed
     *
     * @template B
     *
     * @param callable(V, K): iterable<B> $mapper
     *
     * @return ArrayList<B>
     */
    public function flatMap(callable $mapper): ArrayList
    {
        /** @var array<int, iterable<B>> $mapped */
        $mapped = array_map($mapper, $this->values, $this->keys);

        return ArrayList::fromIterable($mapped)->flatten();
    }
}
