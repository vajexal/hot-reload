<?php

declare(strict_types=1);

namespace Vajexal\HotReload\PathFilter;

class GlobPathFilter implements PathFilter
{
    private array $exclude;

    public function __construct(array $exclude = [])
    {
        $this->exclude = $exclude;
    }

    public function match(string $filepath): bool
    {
        if (\str_starts_with($filepath, './')) {
            $filepath = \mb_substr($filepath, \mb_strlen('./'));
        }

        return \array_reduce($this->exclude, function ($match, $pattern) use ($filepath) {
            if (\str_starts_with($pattern, '!') && \fnmatch(\mb_substr($pattern, 1), $filepath)) {
                return true;
            }

            if (\fnmatch($pattern, $filepath)) {
                return false;
            }

            return $match;
        }, true);
    }
}
