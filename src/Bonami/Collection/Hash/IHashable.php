<?php

namespace Bonami\Collection\Hash;

interface IHashable
{

    /** @phpstan-return int|string */
    public function hashCode();
}
