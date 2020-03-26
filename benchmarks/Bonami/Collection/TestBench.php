<?php declare(strict_types=1);

namespace Bonami\Collection;

use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;

class TestBench {

	/**
	 * @Revs(1000)
	 * @Iterations(1)
	 */
	public function benchLazyList() {
		LazyList::range(0, 100)
			->flatMap(function(int $i): LazyList {
				return LazyList::range($i, $i + 100);
			})
			->reduce(function (int $acc, int $i): int { return $acc + $i; }, 0);
	}

	/**
	 * @Revs(1000)
	 * @Iterations(1)
	 */
	public function benchArayList() {
		ArrayList::range(0, 100)
			->flatMap(function(int $i): ArrayList {
				return ArrayList::range($i, $i + 100);
			})
			->reduce(function (int $acc, int $i): int { return $acc + $i; }, 0);
	}

}

