<?php

namespace Bonami\Collection\Mutable;

use function Bonami\Collection\hashKey;

/**
 * @template K
 * @template V
 * @extends \Bonami\Collection\Map<K, V>
 */
class Map extends \Bonami\Collection\Map {

	/**
	 * @param K $key
	 * @param V $value
	 */
	public function add($key, $value): void {
		$keyHash = hashKey($key);
		$this->keys[$keyHash] = $key;
		$this->values[$keyHash] = $value;
	}

	/**
	 * @param K $key
	 * @param V $value
	 *
	 * @return V
	 */
	public function getOrAdd($key, $value) {
		$keyHash = hashKey($key);
		if (!array_key_exists($keyHash, $this->values)) {
			$this->keys[$keyHash] = $key;
			$this->values[$keyHash] = $value;
		}

		return $this->values[$keyHash];
	}

}
