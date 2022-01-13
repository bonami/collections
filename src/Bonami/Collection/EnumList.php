<?php

declare(strict_types=1);

namespace Bonami\Collection;

/**
 * @template T of Enum
 *
 * @extends ArrayList<T>
 */
class EnumList extends ArrayList
{
    /** @return ArrayList<int|string> */
    public function getValueList(): ArrayList
    {
        return $this->map(static function (Enum $enum) {
            return $enum->getValue();
        });
    }

    /** @return array<int, int|string> */
    public function getValues(): array
    {
        return $this->getValueList()->toArray();
    }
}
