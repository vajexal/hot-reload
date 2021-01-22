<?php

declare(strict_types=1);

namespace Vajexal\HotReload\PathFilter;

interface PathFilter
{
    public function match(string $filepath): bool;
}
