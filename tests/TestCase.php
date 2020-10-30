<?php

namespace Vajexal\HotReload\Tests;

use Amp\File;
use Amp\PHPUnit\AsyncTestCase;

abstract class TestCase extends AsyncTestCase
{
    protected string $dir;
    protected string $filepath;

    protected function setUpAsync()
    {
        $this->dir      = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
        $this->filepath = $this->dir . DIRECTORY_SEPARATOR . 'temp.php';

        yield File\mkdir($this->dir);
    }

    protected function tearDownAsync()
    {
        yield File\unlink($this->filepath);
        yield File\rmdir($this->dir);
    }
}
