<?php declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\ValueIsNotPresentException;
use Bonami\Collection\Hash\IHashable;
use EmptyIterator;
use IteratorAggregate;

abstract class Option implements IHashable, IteratorAggregate {

	use ApplicativeHelpers;

	/** @var Option|null */
	private static $none;

	final public static function of($value): Option {
		return self::some($value);
	}

	final public static function none(): Option {
		return self::$none ?? self::$none = new class extends Option {

			public function isDefined(): bool {
				return false;
			}

			public function map(callable $mapper): Option {
				return $this;
			}

			public function ap(Option $option): Option {
				return $this;
			}

			public function flatMap(callable $mapper): Option {
				return $this;
			}

			public function filter(callable $predicate): Option {
				return $this;
			}

			/** @inheritDoc */
			public function getUnsafe() {
				throw new ValueIsNotPresentException("Can not get value for None");
			}

			public function getOrElse($else) {
				return $else;
			}

			public function toTrySafe(): TrySafe {
				return TrySafe::failure(new ValueIsNotPresentException());
			}

			public function hashCode() {
				return spl_object_hash($this); // There should be only one instance of none
			}

			public function getIterator() {
				return new EmptyIterator();
			}

			public function orElse(Option $else): Option {
				return $else;
			}

			public function __toString(): string {
			    return 'None';
			}

		};
	}

	final public static function some($value): Option {
		return new class($value) extends Option {
			private $value;

			protected function __construct($value) {
				$this->value = $value;
			}

			public function isDefined(): bool {
				return true;
			}

			public function ap(Option $option): Option {
				assert(is_callable($this->value));
				return $option->map(function ($value) {
					return Lambda::of($this->value)($value);
				});
			}

			public function map(callable $mapper): Option {
				return self::of($mapper($this->value));
			}

			public function flatMap(callable $mapper): Option {
				$option = $mapper($this->value);
				assert($option instanceof Option);
				return $option;
			}

			public function filter(callable $predicate): Option {
				return $predicate($this->value) ? $this : self::none();
			}

			/** @inheritDoc */
			public function getUnsafe() {
				return $this->value;
			}

			public function getOrElse($else) {
				return $this->value;
			}

			public function toTrySafe(): TrySafe {
				return TrySafe::success($this->value);
			}

			public function hashCode() {
				$valueHash = $this->value instanceof IHashable
					? $this->value->hashCode()
					: hashKey($this->value);
				return __CLASS__ . "::some({$valueHash})";
			}

			public function getIterator() {
				return new ArrayIterator([$this->value]);
			}

			public function orElse(Option $else): Option {
				return $this;
			}

			public function __toString(): string {
				return 'Some(' . $this->value . ')';
			}

		};
	}

	abstract public function isDefined(): bool;

	abstract public function filter(callable $predicate): Option;

	abstract public function ap(Option $option): Option;

	abstract public function map(callable $mapper): Option;

	abstract public function flatMap(callable $mapper): Option;

	final public function reduce(callable $reducer, $initialReduction) {
		return LazyList::fromIterable($this)->reduce($reducer, $initialReduction);
	}

	/**
	 * Consider calling getOrElse instead
	 * @throws ValueIsNotPresentException
	 */
	abstract public function getUnsafe();

	abstract public function getOrElse($else);

	abstract public function toTrySafe(): TrySafe;

	abstract public function orElse(self $else): self;

	final public function equals($value): bool {
		return $value instanceof Option
			&& $value->hashCode() === $this->hashCode();
	}
}
