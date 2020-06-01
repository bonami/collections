<?php declare(strict_types=1);

namespace Bonami\Collection;

trait ApplicativeHelpers {

	/**
	 * Upgrades callable to accept and return `self` as arguments.
	 *
	 * @param callable $callable
	 * @return callable
	 */
	final public static function lift(callable $callable): callable {
		return function (self ...$arguments) use ($callable): self {
			return LazyList::fromIterable($arguments)
				->reduce(
					function (self $applicative, self $argument): self {
						return $applicative->ap($argument);
					},
					self::of($callable)
				);
		};
	}

	/**
	 * Takes any `iterable<A>`, for each item `A` transforms to applicative with $mapperToApplicative
	 * `A => self<B>` and cumulates it in `self<ArrayList<B>>`.
	 *
	 * @param iterable<mixed> $iterable             - iterable<A>
	 * @param callable(mixed): self<mixed> $mapperToApplicative  - A => self<B>
	 *
	 *                                         When omitted, identity is used. That is useful when
	 *                                         iterable already contains self instances
	 *
	 * @see sequence - behaves same as traverse, execept it is called with identity
	 *
	 * @return self<ArrayList<mixed>>
	 */
	final public static function traverse(iterable $iterable, callable $mapperToApplicative = null): self {
		$mapperToApplicative = $mapperToApplicative ?? identity();
		return LazyList::fromIterable($iterable)
			->reduce(
				function(self $reducedApplicative, $impureItem) use ($mapperToApplicative): self {
					$applicative = $mapperToApplicative($impureItem);
					assert($applicative instanceof self);
					return $reducedApplicative
						->map(function (ArrayList $resultIterable): callable {
							return function ($item) use ($resultIterable): ArrayList {
								return $resultIterable->concat(ArrayList::of($item));
							};
						})
						->ap($applicative);
				},
				self::of(ArrayList::fromEmpty())
			);
	}

	/**
	 * Takes any `iterable<self<A>>` and sequence it into `self<ArrayList<A>>`. If any `self` is "empty", the result is
	 * "short circuited".
	 *
	 * E. g. when called upon Option, when any instance is a None, then result is None. If all instances are Some, the result
	 * is Some<ArrayList<A>>
	 *
	 * @param iterable<mixed> $iterable             - iterable<self<A>>
	 *
	 * @return self<ArrayList<mixed>>
	 */
	final public static function sequence(iterable $iterable): self {
		return self::traverse($iterable, identity());
	}
}
