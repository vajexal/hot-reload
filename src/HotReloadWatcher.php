<?php

namespace Vajexal\HotReload;

use Amp\Delayed;
use Amp\Process\Process;
use Amp\Promise;
use function Amp\asyncCall;
use function Amp\ByteStream\getStderr;
use function Amp\ByteStream\getStdout;
use function Amp\ByteStream\pipe;
use function Amp\call;

class HotReloadWatcher
{
    private FilesystemWatcher $filesystemWatcher;
    private Process           $process;

    public function __construct(string $path, $command)
    {
        asyncCall(function () use ($path, $command) {
            $this->process = yield $this->startProcess($command);

            pipe($this->process->getStdout(), getStdout());
            pipe($this->process->getStderr(), getStderr());

            $this->filesystemWatcher = new FilesystemWatcher($path, function () use ($command) {
                yield $this->gracefullyStopProcess();

                $this->process = yield $this->startProcess($command);

                pipe($this->process->getStdout(), getStdout());
                pipe($this->process->getStderr(), getStderr());
            });
        });
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

            $this->process->signal(SIGINT);
            yield new Delayed(50);

            if (!$this->process->isRunning()) {
                return;
            }

            $this->process->kill();
            yield new Delayed(50);

            if (!$this->process->isRunning()) {
                return;
            }

            yield $this->process->join();
        });
    }
}
