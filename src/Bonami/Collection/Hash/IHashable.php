<?php

declare(strict_types=1);

namespace Bonami\Collection\Hash;

interface IHashable
{
    public function hashCode(): int|string;
}
