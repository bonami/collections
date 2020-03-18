<?php declare(strict_types=1);

namespace Bonami\Collection;

use ArrayIterator;
use Bonami\Collection\Exception\ValueIsNotPresentException;
use Bonami\Collection\Hash\IHashable;
use EmptyIterator;
use IteratorAggregate;
use Throwable;

abstract class TrySafe implements IHashable, IteratorAggregate {

	use ApplicativeHelpers;

	final public static function of($value): TrySafe {
		return self::success($value);
	}

	final public static function fromCallable(callable $callable): TrySafe {
		try {
			return self::success($callable());
		} catch (Throwable $failure) {
			return self::failure($failure);
		}
	}

	final public static function success($value): TrySafe {
		return new class($value) extends TrySafe {

			private $value;

			protected function __construct($value) {
				$this->value = $value;
			}

			public function isSuccess(): bool {
				return true;
			}

			public function map(callable $mapper): TrySafe {
				return self::fromCallable(function () use ($mapper) {
					return $mapper($this->value);
				});
			}

			public function ap(TrySafe $trySafe): TrySafe {
				assert(is_callable($this->value));
				return $trySafe->map(function ($value) {
					return Lambda::of($this->value)($value);
				});
			}

			public function flatMap(callable $mapper): TrySafe {
				try {
					$trySafe = $mapper($this->value);
				} catch (Throwable $failure) {
					return self::failure($failure);
				}

				assert($trySafe instanceof TrySafe);
				return $trySafe;
			}

			public function recover(callable $callable): TrySafe {
				return $this;
			}

			/** @inheritDoc */
			public function getUnsafe() {
				return $this->value;
			}

			public function getOrElse($else) {
				return $this->value;
			}

			/** @inheritDoc */
			public function getFailureUnsafe(): Throwable {
				throw new ValueIsNotPresentException("Can not get failure for Success");
			}

			public function toOption(): Option {
				return Option::some($this->value);
			}

			public function fold(callable $handleFailure, callable $handleSuccess) {
				return $handleSuccess($this->value);
			}

			public function getIterator() {
				return new ArrayIterator([$this->value]);
			}

			public function hashCode() {
				$valueHash = $this->value instanceof IHashable
					? $this->value->hashCode()
					: hashKey($this->value);
				return __CLASS__ . "::success({$valueHash})";
			}
		};
	}

	final public static function failure(Throwable $failure): TrySafe {
		return new class($failure) extends TrySafe {

			/** @var Throwable */
			private $failure;

			protected function __construct(Throwable $failure) {
				$this->failure = $failure;
			}

			public function isSuccess(): bool {
				return false;
			}

			public function map(callable $mapper): TrySafe {
				return $this;
			}

			public function ap(TrySafe $trySafe): TrySafe {
				return $this;
			}

			public function flatMap(callable $mapper): TrySafe {
				return $this;
			}

			public function recover(callable $callable): TrySafe {
				return self::fromCallable(function () use ($callable) {
					return $callable($this->failure);
				});
			}

			/** @inheritDoc */
			public function getUnsafe() {
				throw new ValueIsNotPresentException("Can not get value for Failure");
			}

			public function getOrElse($else) {
				return $else;
			}

			/** @inheritDoc */
			public function getFailureUnsafe(): Throwable {
				return $this->failure;
			}

			public function toOption(): Option {
				return Option::none();
			}

			public function fold(callable $handleFailure, callable $handleSuccess) {
				return $handleFailure($this->failure);
			}

			public function getIterator() {
				return new EmptyIterator();
			}

			public function hashCode() {
				$failureHash = $this->failure instanceof IHashable
					? $this->failure->hashCode()
					: hashKey($this->failure);
				return __CLASS__ . "::failure({$failureHash})";
			}
		};
	}

	abstract public function ap(TrySafe $trySafe): TrySafe;

	abstract public function map(callable $mapper): TrySafe;

	abstract public function flatMap(callable $mapper): TrySafe;

	final public function reduce(callable $reducer, $initialReduction) {
		return LazyList::fromIterable($this)->reduce($reducer, $initialReduction);
	}

	final public function equals($value): bool {
		return $value instanceof TrySafe
			&& $value->hashCode() === $this->hashCode();
	}

	abstract public function recover(callable $callable): TrySafe;

	abstract public function isSuccess(): bool;

	final public function isFailure(): bool {
		return !$this->isSuccess();
	}

	/**
	 * Consider calling getOrElse instead
	 * @throws ValueIsNotPresentException
	 */
	abstract public function getUnsafe();

	abstract public function getOrElse($else);

	/**
	 * @throws ValueIsNotPresentException
	 */
	abstract public function getFailureUnsafe(): Throwable;

	abstract public function toOption(): Option;

	abstract public function fold(callable $handleFailure, callable $handleSuccess);

}
