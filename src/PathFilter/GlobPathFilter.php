<?php

declare(strict_types=1);

namespace Vajexal\HotReload\PathFilter;

class GlobPathFilter implements PathFilter
{
    private array  $excludeDirPatterns;
    private string $filePattern;

    public function exclude(string $pattern): self
    {
        $this->excludeDirPatterns[] = $pattern;

        return $this;
    }

    public function filePattern(string $pattern): self
    {
        $this->filePattern = $pattern;

        return $this;
    }

    public function matchDir(string $filepath): bool
    {
        if (\str_starts_with($filepath, './')) {
            $filepath = \mb_substr($filepath, \mb_strlen('./'));
        }

        return \array_reduce($this->excludeDirPatterns, function ($match, $pattern) use ($filepath) {
            if (\str_starts_with($pattern, '!') && \fnmatch(\mb_substr($pattern, 1), $filepath)) {
                return true;
            }

            if (\fnmatch($pattern, $filepath)) {
                return false;
            }

            return $match;
        }, true);
    }

    public function matchFile(string $filepath): bool
    {
        if (!$this->filePattern) {
            return true;
        }

        return \fnmatch($this->filePattern, \basename($filepath));
    }
}
