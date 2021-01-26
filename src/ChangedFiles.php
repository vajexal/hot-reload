<?php

declare(strict_types=1);

namespace Vajexal\HotReload;

class ChangedFiles
{
    private array $added;
    private array $modified;
    private array $deleted;

    private function __construct()
    {
    }

    public static function fromCachesDiff(array $oldCache, array $newCache): self
    {
        $changedFiles = new self;

        $changedFiles->added    = \array_keys(\array_diff_key($newCache, $oldCache));
        $changedFiles->modified = \array_filter(
            \array_keys($newCache),
            fn ($filepath) => isset($oldCache[$filepath]) && $newCache[$filepath] !== $oldCache[$filepath]
        );

        $changedFiles->deleted = \array_keys(\array_diff_key($oldCache, $newCache));

        return $changedFiles;
    }

    public function getAdded(): array
    {
        return $this->added;
    }

    public function getModified(): array
    {
        return $this->modified;
    }

    public function getDeleted(): array
    {
        return $this->deleted;
    }

    public function hasChanges(): bool
    {
        return $this->added || $this->modified || $this->deleted;
    }
}
