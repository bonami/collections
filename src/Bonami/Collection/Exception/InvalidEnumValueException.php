<?php

namespace Bonami\Collection\Exception;

use InvalidArgumentException;
use function get_class;
use function gettype;

class InvalidEnumValueException extends InvalidArgumentException {

	public function __construct($value, string $enumClass) {
		$valueType = gettype($value);
		$expectedValues = $enumClass::instanceList()->join(', ');

		$message = $valueType === 'object'
			? sprintf("Invalid value '%s', one of scalar %s expected", get_class($value), $expectedValues)
			: sprintf("Invalid %s value '%s', one of %s expected", $valueType, $value, $expectedValues);

		parent::__construct($message);
	}
}
