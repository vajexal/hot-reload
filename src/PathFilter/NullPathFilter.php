<?php

declare(strict_types=1);

namespace Vajexal\HotReload\PathFilter;

class NullPathFilter implements PathFilter
{
    public function match(string $filepath): bool
    {
        return true;
    }
}
