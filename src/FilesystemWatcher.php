<?php

namespace Vajexal\HotReload;

use Amp\File;
use Amp\Iterator;
use Amp\Loop;
use Amp\Producer;
use Amp\Promise;
use Vajexal\HotReload\PathFilter\NullPathFilter;
use Vajexal\HotReload\PathFilter\PathFilter;
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
            yield $this->filesChanged($path);

            $this->watcherId = Loop::repeat(self::POLLING_INTERVAL, function () use ($path, $callback) {
                if (yield $this->filesChanged($path)) {
                    Promise\rethrow(call($callback));
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
     * @return Promise<bool>
     */
    private function filesChanged(string $path): Promise
    {
        return call(function () use ($path) {
            $newCache = [];

            $files = $this->scandirRecursive($path);

            $files = Iterator\filter($files, function ($file) {
                return \fnmatch('*.php', $file);
            });

            while (yield $files->advance()) {
                $file            = $files->getCurrent();
                $newCache[$file] = yield File\mtime($file);
            }

            \ksort($newCache);

            $filesChanged = $this->cache !== $newCache;

            $this->cache = $newCache;

            return $filesChanged;
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

                if (!$this->pathFilter->match($filepath)) {
                    continue;
                }

                if (yield File\isdir($filepath)) {
                    $iterator = $this->scandirRecursive($filepath);

                    while (yield $iterator->advance()) {
                        $emit($iterator->getCurrent());
                    }

                    continue;
                }

                $emit($filepath);
            }
        });
    }
}
