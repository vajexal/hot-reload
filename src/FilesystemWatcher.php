<?php

namespace Vajexal\HotReload;

use Amp\File;
use Amp\Loop;
use Amp\Promise;
use function Amp\asyncCall;
use function Amp\call;

class FilesystemWatcher
{
    const POOLING_INTERVAL = 250;

    private array  $cache = [];
    private string $watcherId;

    public function __construct(string $path, callable $callback)
    {
        asyncCall(function () use ($path, $callback) {
            // Warm up cache
            yield $this->filesChanged($path);

            $this->watcherId = Loop::repeat(self::POOLING_INTERVAL, function () use ($path, $callback) {
                if (yield $this->filesChanged($path)) {
                    asyncCall($callback);
                }
            });
        });
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

            $files = yield $this->scandirRecursive($path, ['./vendor', './.idea', './.git']); // todo maybe chunked or iterator

            foreach ($files as $file) {
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
     * @return Promise<array>
     */
    private function scandirRecursive(string $path, array $exclude = []): Promise
    {
        return call(function () use ($path, $exclude) {
            $result = [];

            $files = yield File\scandir($path);

            foreach ($files as $file) {
                $filepath = $path . DIRECTORY_SEPARATOR . $file;

                if (\in_array($filepath, $exclude, true)) {
                    continue;
                }

                if (yield File\isdir($filepath)) {
                    $result = \array_merge($result, yield $this->scandirRecursive($filepath, $exclude));

                    continue;
                }

                $result[] = $filepath;
            }

            return $result;
        });
    }
}