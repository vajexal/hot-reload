<?php

declare(strict_types=1);

namespace Vajexal\HotReload;

class Config
{
    private bool   $logChanges      = false;
    private bool   $clearScreen     = false;
    private array  $excludePatterns = ['vendor', '.git', '.idea'];
    private string $filePattern     = '*.php';
    private array  $command         = [];

    /**
     * @return static
     * @throws ConfigException
     */
    public static function createFromArgv(): self
    {
        $config = new static;

        if (!$_SERVER['argv']) {
            return $config;
        }

        for ($i = 1; $i < \count($_SERVER['argv']); $i++) {
            switch ($_SERVER['argv'][$i]) {
                case '--log-changes':
                    $config->logChanges = true;

                    break;
                case '--clear-screen':
                    $config->clearScreen = true;

                    break;
                case '--exclude':
                    if (empty($_SERVER['argv'][++$i])) {
                        throw ConfigException::missingExcludePattern();
                    }

                    $config->excludePatterns[] = $_SERVER['argv'][$i];

                    break;
                case '--include':
                    if (empty($_SERVER['argv'][++$i])) {
                        throw ConfigException::missingIncludePattern();
                    }

                    $config->excludePatterns = \array_filter($config->excludePatterns, fn ($pattern) => $pattern !== $_SERVER['argv'][$i]);

                    break;
                case '--file-pattern':
                    if (empty($_SERVER['argv'][++$i])) {
                        throw ConfigException::missingFilePattern();
                    }

                    $config->filePattern = $_SERVER['argv'][$i];

                    break;
                default:
                    $config->command = \array_slice($_SERVER['argv'], $i);

                    return $config;
            }
        }

        throw ConfigException::missingCommand();
    }

    public function shouldLogChanges(): bool
    {
        return $this->logChanges;
    }

    public function shouldClearScreen(): bool
    {
        return $this->clearScreen;
    }

    public function getExcludePatterns(): array
    {
        return $this->excludePatterns;
    }

    public function getFilePattern(): string
    {
        return $this->filePattern;
    }

    public function getCommand(): array
    {
        return $this->command;
    }
}
