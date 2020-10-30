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

            $this->filesystemWatcher = new FilesystemWatcher($path, function () use ($command) {
                yield $this->gracefullyStopProcess();

                $this->process = yield $this->startProcess($command);
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
            $env = \getenv();

            if ($this->hasColorSupport()) {
                // We should tell subprocess that we support colorful output
                $env = [
                        'AMP_LOG_COLOR' => true, // For amphp/log
                        'ANSICON'       => true, // For windows
                        'TERM_PROGRAM'  => 'Hyper', // For symfony/console
                    ] + $env;
            }

            $process = new Process($command, null, $env);
            yield $process->start();

            Promise\rethrow(pipe($process->getStdout(), getStdout()));
            Promise\rethrow(pipe($process->getStderr(), getStderr()));

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

    /**
     * @return bool
     * @link https://github.com/symfony/console/blob/v5.1.8/Output/StreamOutput.php#L94
     */
    private function hasColorSupport(): bool
    {
        // Follow https://no-color.org/
        if (isset($_SERVER['NO_COLOR']) || false !== \getenv('NO_COLOR')) {
            return false;
        }

        if ('Hyper' === \getenv('TERM_PROGRAM')) {
            return true;
        }

        if (\DIRECTORY_SEPARATOR === '\\') {
            return (\function_exists('sapi_windows_vt100_support')
                    && @sapi_windows_vt100_support(\STDOUT))
                || false !== \getenv('ANSICON')
                || 'ON' === \getenv('ConEmuANSI')
                || 'xterm' === \getenv('TERM');
        }

        return \stream_isatty(\STDOUT);
    }
}
