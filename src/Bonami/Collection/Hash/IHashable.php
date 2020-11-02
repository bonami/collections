<?php

declare(strict_types=1);

namespace Bonami\Collection\Hash;

interface IHashable
{

    /** @phpstan-return int|string */
    public function hashCode();
}
