<?php

declare(strict_types=1);

namespace Vajexal\HotReload\PathFilter;

interface PathFilter
{
    public function matchDir(string $filepath): bool;
    public function matchFile(string $filepath): bool;
}
