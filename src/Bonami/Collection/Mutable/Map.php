<?php

namespace Bonami\Collection\Mutable;

use function Bonami\Collection\hashKey;

class Map extends \Bonami\Collection\Map {

	public function add($key, $value): void {
		$keyHash = hashKey($key);
		$this->keys[$keyHash] = $key;
		$this->values[$keyHash] = $value;
	}

	public function getOrAdd($key, $value) {
		$keyHash = hashKey($key);
		if (!array_key_exists($keyHash, $this->values)) {
			$this->keys[$keyHash] = $key;
			$this->values[$keyHash] = $value;
		}

		return $this->values[$keyHash];
	}

}
