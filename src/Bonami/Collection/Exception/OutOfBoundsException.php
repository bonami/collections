<?php

declare(strict_types=1);

namespace Bonami\Collection\Exception;

use InvalidArgumentException;

class OutOfBoundsException extends InvalidArgumentException implements CollectionException
{

}
