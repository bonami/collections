<?php

declare(strict_types=1);

namespace Bonami\Collection;

/**
 * @template T of Enum
 *
 * @phpstan-extends ArrayList<T>
 */
class EnumList extends ArrayList
{

    /** @phpstan-return ArrayList<int|string> */
    public function getValueList(): ArrayList
    {
        return $this->map(static function (Enum $enum) {
            return $enum->getValue();
        });
    }

    /** @phpstan-return array<int, int|string> */
    public function getValues(): array
    {
        return $this->getValueList()->toArray();
    }
}
