<?php

declare(strict_types=1);

namespace Bonami\Collection\Exception;

use Bonami\Collection\Enum;
use InvalidArgumentException;

use function get_class;
use function gettype;

class InvalidEnumValueException extends InvalidArgumentException
{
    /**
     * @param mixed $value
     * @param class-string<Enum> $enumClass
     */
    public function __construct($value, string $enumClass)
    {
        $valueType = gettype($value);
        $expectedValues = $enumClass::instanceList()->join(', ');

        $message = $valueType === 'object'
            ? sprintf('Invalid value "%s", one of scalar %s expected', $value::class, $expectedValues)
            : sprintf('Invalid %s value "%s", one of %s expected', $valueType, $value, $expectedValues);

        parent::__construct($message);
    }
}
