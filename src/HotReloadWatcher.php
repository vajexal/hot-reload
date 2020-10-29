<?php

namespace Vajexal\HotReload;

use Amp\Process\Process;
use Amp\Promise;
use Amp\TimeoutException;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;
use function Amp\call;

class HotReloadWatcher
{
    private const PROCESS_CLOSE_TIMEOUT = 50;

    private FilesystemWatcher $filesystemWatcher;
    private Process           $process;

    public function __construct(string $path, $command)
    {
        Promise\rethrow(call(function () use ($path, $command) {
            $this->process = yield $this->startProcess($command);

            Promise\rethrow(pipe($this->process->getStdout(), getStdout()));
            Promise\rethrow(pipe($this->process->getStderr(), getStderr()));

            $this->filesystemWatcher = new FilesystemWatcher($path, function () use ($command) {
                yield $this->gracefullyStopProcess();

                // todo debug loop and check watchers

                $this->process = yield $this->startProcess($command);

                Promise\rethrow(pipe($this->process->getStdout(), getStdout()));
                Promise\rethrow(pipe($this->process->getStderr(), getStderr()));
            });
        }));
    }

    /**
     * @return Promise<null>
     */
    public function unwatch(): Promise
    {
        return call(function () {
            if ($this->filesystemWatcher) {
                yield $this->filesystemWatcher->unwatch();
            }

            if ($this->process) {
                yield $this->gracefullyStopProcess();
            }
        });
    }

    /**
     * @param array|string $command
     * @return Promise<Process>
     */
    private function startProcess($command): Promise
    {
        return call(function () use ($command) {
            $process = new Process($command);
            yield $process->start();
            return $process;
        });
    }

    /**
     * @return Promise<null>
     */
    private function gracefullyStopProcess(): Promise
    {
        return call(function () {
            if (!$this->process->isRunning()) {
                return;
            }

            try {
                $this->process->signal(SIGINT);
                yield Promise\timeout($this->process->join(), self::PROCESS_CLOSE_TIMEOUT);
            } catch (TimeoutException $e) {
                $this->process->kill();
            }
        });
    }
}
