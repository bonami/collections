<?php

namespace Bonami\Collection;

class EnumList extends ArrayList {

	public function getValueList(): ArrayList {
		return $this->map(static function (Enum $enum) { return $enum->getValue(); });
	}

	public function getValues(): array {
		return $this->getValueList()->toArray();
	}

}
