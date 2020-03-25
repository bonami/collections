<?php declare(strict_types = 1);

namespace Bonami\Collection;

use ArrayIterator;
use Generator;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use RuntimeException;
use SplFixedArray;
use Traversable;

class LazyList implements IteratorAggregate {

	use ApplicativeHelpers;

	/** @var iterable */
	private $items;

	public function __construct(iterable $iterable) {
		$this->items = $iterable;
	}

	public static function range(int $low, int $high = PHP_INT_MAX, int $step = 1): self {
		$range = static function(int $low, int $high, int $step = 1): Generator {
			while ($low <= $high) {
				yield $low;
				$low += $step;
			}
		};

		return new static($range($low, $high, $step));
	}

	public static function fill($item, ?int $size = null): self {
		$fill = static function($item, ?int $size = null): Generator {
			$generated = 0;
			while ($size === null ? true : $size > $generated) {
				yield $item;
				if ($size !== null) {
					$generated++;
				}
			}
		};

		return new static($fill($item, $size));
	}

	public static function fromEmpty(): self {
		return new self([]);
	}

	public static function fromArray(array ...$items): self {
		return self::fromEmpty()->concat(...$items);
	}

	public static function fromTraversable(Traversable $items): self {
		return new self($items);
	}

	public static function fromIterable(iterable $iterable): self {
		return new self($iterable);
	}

	public static function of(...$items): self {
		return new self($items);
	}

	public function map(callable $mapper) {
		$map = function(callable $callback): Generator {
			foreach ($this->items as $key => $item) {
				yield $callback($item, $key);
			}
		};
		return new static($map($mapper));
	}

	public function ap(self $lazyList): self {
		$mappers = $this->map(function (callable $mapper) { return Lambda::of($mapper); })->toList();

		return $lazyList->flatMap(function ($value) use ($mappers) {
			return $mappers->map(function (Lambda $mapper) use ($value) {
				return ($mapper)($value);
			});
		});
	}

	public function flatMap(callable $mapper): self {
		return $this->map($mapper)->flatten();
	}

	public function flatten(): self {
		$flatten = function (): Generator {
			foreach ($this->items as $item) {
				if (is_iterable($item)) {
					yield from $item;
				} else {
					throw new RuntimeException('Some item cannot be flattened because it is not iterable');
				}
			}
		};
		return new self($flatten());
	}

	public function each(callable $sideEffect): void {
		foreach ($this->items as $item) {
			$sideEffect($item);
		}
	}

	public function reduce(callable $reducer, $initialReduction) {
		$reduction = $initialReduction;
		foreach ($this->items as $item) {
			$reduction = $reducer($reduction, $item);
		}
		return $reduction;
	}

	/**
	 * Computes a prefix scan (reduction) of the elements of the collection.
	 *
	 * @param callable $scanner a binary operation for scan (reduction)
	 * @param mixed $initialReduction
	 *
	 * @return self collection with intermediate scan (reduction) results
	 */
	public function scan(callable $scanner, $initialReduction): self {
		$scan = function (callable $scanner, $initialReduction): Generator {
			$prefixReduction = $initialReduction;
			foreach ($this->items as $item) {
				$prefixReduction = $scanner($prefixReduction, $item);
				yield $prefixReduction;
			}
		};

		return new self($scan($scanner, $initialReduction));
	}

	public function takeWhile(callable $predicate) {
		$takeWhile = function(callable $whileCallback): Generator {
			foreach ($this->items as $item) {
				if (!$whileCallback($item)) {
					break;
				}
				yield $item;
			}
		};
		return new static($takeWhile($predicate));
	}

	public function take(int $size) {
		$take = function(int $size): Generator {
			$taken = 1;
			foreach ($this->items as $item) {
				yield $item;
				$taken++;
				if ($taken > $size) {
					break;
				}
			}
		};
		return new static($take($size));
	}

	public function chunk(int $size): self {
		assert($size > 0, 'Size must be positive');
		$chunk = function (int $size): Generator {
			$materializedChunk = [];
			foreach ($this->items as $item) {
				$materializedChunk[] = $item;
				if (count($materializedChunk) === $size) {
					yield static::fromArray($materializedChunk);
					$materializedChunk = [];
				}
			}

			if (count($materializedChunk) !== 0) {
				yield static::fromArray($materializedChunk);
			}
		};

		return new self($chunk($size));
	}

	public function head(): Option {
		return $this->find(tautology());
	}

	public function last(): Option {
		// No first item implies there is also no last item, thus we have to return none
		return $this->head()->isDefined()
			? Option::some($this->reduce(static function ($_, $item) { return $item; }, null))
			: Option::none();
	}

	public function filter(callable $predicate) {
		$filter = function (callable $filterCallback): Generator {
			foreach ($this->items as $item) {
				if ($filterCallback($item)) {
					yield $item;
				}
			}
		};
		return new static($filter($predicate));
	}

	public function find(callable $predicate): Option {
		foreach ($this->items as $item) {
			if ($predicate($item)) {
				return Option::some($item);
			}
		}

		return Option::none();
	}

	public function dropWhile(callable $predicate) {
		$drop = function (callable $dropCallback): Generator {
			$dropping = true;
			foreach ($this->items as $item) {
				if ($dropping && $dropCallback($item)) {
					continue;
				}

				$dropping = false;

				yield $item;
			}
		};

		return new static($drop($predicate));
	}

	public function drop(int $count) {
		$i = 0;
		return $this->dropWhile(static function () use ($count, &$i) {
			return $i++ < $count;
		});
	}

	public function exists(callable $predicate): bool {
		foreach ($this->items as $item) {
			if ($predicate($item)) {
				return true;
			}
		}

		return false;
	}

	public function all(callable $predicate): bool {
		foreach ($this->items as $item) {
			if (!$predicate($item)) {
				return false;
			}
		}

		return true;
	}

	public function zip(iterable ...$iterables): self {
		$zip = function (array $iterables): Generator {
			$traversables = new self(
				array_map(
					function(iterable $iterable): Traversable { return $this->createTraversable($iterable); },
					array_merge([$this->getIterator()], $iterables)
				)
			);

			$rewind = static function(Iterator $iterator): void { $iterator->rewind(); };
			$isValid = static function(Iterator $iterator): bool { return $iterator->valid(); };
			$moveNext = static function (Iterator $iterator): void { $iterator->next(); };

			$traversables->each($rewind);
			while ($traversables->all($isValid)) {
				yield $traversables->map(static function(Iterator $iterator) { return $iterator->current(); })->toArray();
				$traversables->each($moveNext);
			}
		};
		return new self($zip($iterables));
	}

	public function concat(iterable ...$iterables) {
		$append = function (iterable ...$iterables): Generator {
			yield from $this;
			foreach (SplFixedArray::fromArray(...$iterables) as $iterator) {
				yield from $iterator;
			}
		};
		return new static($append($iterables));
	}

	public function add(...$items) {
		return $this->concat(new self($items));
	}

	public function insertOnPosition(int $position, iterable $iterable) {
		$insertOnPosition = function (int $position, iterable $iterable): Generator {
			$index = 0;
			foreach ($this->items as $item) {
				if ($index === $position) {
					yield from $iterable;
				}
				$index++;
				yield $item;
			}
			if ($position >= $index) {
				throw new InvalidArgumentException("Tried to insert collection to position {$position}, but only {$index} items were found");
			}
		};
		return new static($insertOnPosition($position, $iterable));
	}

	public function toArray(): array {
		return iterator_to_array($this->getIterator(), false);
	}

	public function join(string $glue): string {
		return implode($glue, $this->toArray());
	}

	public function getIterator(): Traversable {
		return $this->createTraversable($this->items);
	}

	public function toList(): ArrayList {
		return ArrayList::fromIterable($this);
	}

	private function createTraversable(iterable $iterable): Traversable {
		if ($iterable instanceof Iterator) {
			return $iterable;
		}

		if ($iterable instanceof IteratorAggregate) {
			return $iterable->getIterator();
		}

		if (is_array($iterable)) {
			return new ArrayIterator($iterable);
		}

		// Fallback to generator, be aware, that it is not rewindable!
		return (function(iterable $iterable): Generator {
			yield from $iterable;
		})($iterable);
	}
}
