<?php

declare(strict_types=1);

namespace Bonami\Collection;

interface CallSpy
{
    public function __invoke(): void;

    /** @phpstan-return array<int, array<mixed>> */
    public function getCalls(): array;
}
