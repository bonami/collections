<?php

declare(strict_types=1);

namespace Bonami\Collection\Exception;

use RuntimeException;

class ValueIsNotPresentException extends RuntimeException implements CollectionException
{
}
