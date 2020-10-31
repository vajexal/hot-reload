<?php

namespace Vajexal\HotReload;

use Amp\File;
use Amp\Iterator;
use Amp\Loop;
use Amp\Producer;
use Amp\Promise;
use function Amp\call;

class FilesystemWatcher
{
    const POLLING_INTERVAL = 250;

    private array  $cache = [];
    private string $watcherId;

    public function __construct(string $path, callable $callback)
    {
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

            $files = $this->scandirRecursive($path, [
                '.' . DIRECTORY_SEPARATOR . 'vendor',
                '.' . DIRECTORY_SEPARATOR . '.idea',
                '.' . DIRECTORY_SEPARATOR . '.git',
            ]);

            $files = Iterator\filter($files, function ($file) {
                return \mb_substr($file, -4) === '.php';
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
     * @param array $exclude
     * @return Iterator
     */
    private function scandirRecursive(string $path, array $exclude = []): Iterator
    {
        return new Producer(function ($emit) use ($path, $exclude) {
            $files = yield File\scandir($path);

            foreach ($files as $file) {
                $filepath = $path . DIRECTORY_SEPARATOR . $file;

                if (\in_array($filepath, $exclude, true)) {
                    continue;
                }

                if (yield File\isdir($filepath)) {
                    $iterator = $this->scandirRecursive($filepath, $exclude);

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
