<?php

namespace Vajexal\HotReload;

use Amp\File;
use Amp\Iterator;
use Amp\Loop;
use Amp\Producer;
use Amp\Promise;
use Vajexal\HotReload\PathFilter\NullPathFilter;
use Vajexal\HotReload\PathFilter\PathFilter;
use function Amp\ByteStream\getStdout;
use function Amp\call;

class FilesystemWatcher
{
    const POLLING_INTERVAL = 250;

    private PathFilter $pathFilter;
    private array      $cache = [];
    private string     $watcherId;

    public function __construct(string $path, callable $callback, ?PathFilter $pathFilter = null)
    {
        $this->pathFilter = $pathFilter ?: new NullPathFilter;

        Promise\rethrow(call(function () use ($path, $callback) {
            // Warm up cache
            yield $this->changedFiles($path);

            $this->watcherId = Loop::repeat(self::POLLING_INTERVAL, function () use ($path, $callback) {
                /** @var ChangedFiles $changedFiles */
                $changedFiles = yield $this->changedFiles($path);

                if ($changedFiles->hasChanges()) {
                    if (\getenv('HOTRELOAD_LOG_CHANGED_FILES')) {
                        $changes = \array_merge(
                            \array_map(fn ($filepath) => \sprintf('Added: %s', $filepath), $changedFiles->getAdded()),
                            \array_map(fn ($filepath) => \sprintf('Modified: %s', $filepath), $changedFiles->getModified()),
                            \array_map(fn ($filepath) => \sprintf('Deleted: %s', $filepath), $changedFiles->getDeleted())
                        );

                        yield getStdout()->write(PHP_EOL . \implode(PHP_EOL, $changes) . PHP_EOL . PHP_EOL);
                    }

                    yield call($callback);
                }
            });
        }));
    }

    /**
     * @return Promise<null>
     */
    public function unwatch(): Promise
    {
        return call(function () {
            if ($this->watcherId) {
                Loop::cancel($this->watcherId);
            }
        });
    }

    /**
     * @param string $path
     * @return Promise<ChangedFiles>
     */
    private function changedFiles(string $path): Promise
    {
        return call(function () use ($path) {
            $newCache = [];

            $files = $this->scandirRecursive($path);

            while (yield $files->advance()) {
                $file            = $files->getCurrent();
                $newCache[$file] = yield File\mtime($file);
            }

            $changedFiles = ChangedFiles::fromCachesDiff($this->cache, $newCache);

            $this->cache = $newCache;

            return $changedFiles;
        });
    }

    /**
     * @param string $path
     * @return Iterator
     */
    private function scandirRecursive(string $path): Iterator
    {
        return new Producer(function ($emit) use ($path) {
            $files = yield File\scandir($path);

            foreach ($files as $file) {
                $filepath = $path . DIRECTORY_SEPARATOR . $file;

                if (yield File\isdir($filepath)) {
                    if (!$this->pathFilter->matchDir($filepath)) {
                        continue;
                    }

                    $iterator = $this->scandirRecursive($filepath);

                    while (yield $iterator->advance()) {
                        $emit($iterator->getCurrent());
                    }

                    continue;
                }

                if (!$this->pathFilter->matchFile($filepath)) {
                    continue;
                }

                $emit($filepath);
            }
        });
    }
}
