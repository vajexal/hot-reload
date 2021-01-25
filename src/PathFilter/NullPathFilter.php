<?php

declare(strict_types=1);

namespace Vajexal\HotReload\PathFilter;

class NullPathFilter implements PathFilter
{
    public function matchDir(string $filepath): bool
    {
        return true;
    }

    public function matchFile(string $filepath): bool
    {
        return true;
    }
}
