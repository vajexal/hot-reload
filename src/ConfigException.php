<?php

declare(strict_types=1);

namespace Vajexal\HotReload;

use Exception;

class ConfigException extends Exception
{
    public const MISSING_EXCLUDE_PATTERN = 0;
    public const MISSING_INCLUDE_PATTERN = 1;
    public const MISSING_FILE_PATTERN    = 2;
    public const MISSING_COMMAND         = 3;

    public static function missingExcludePattern(): self
    {
        return new static('Missing exclude pattern', self::MISSING_EXCLUDE_PATTERN);
    }

    public static function missingIncludePattern(): self
    {
        return new static('Missing include pattern', self::MISSING_INCLUDE_PATTERN);
    }

    public static function missingFilePattern(): self
    {
        return new static('Missing file pattern', self::MISSING_FILE_PATTERN);
    }

    public static function missingCommand(): self
    {
        return new static('Missing command', self::MISSING_COMMAND);
    }
}
