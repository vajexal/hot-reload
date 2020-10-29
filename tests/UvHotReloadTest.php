<?php

namespace Vajexal\HotReload\Tests;

use Amp\File;
use Amp\File\UvDriver;
use Amp\Loop;

class UvHotReloadTest extends HotReloadTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!\extension_loaded('uv')) {
            $this->markTestSkipped('uv extension is not loaded');
        }

        $loop = new Loop\UvDriver;

        Loop::set($loop);

        File\filesystem(new UvDriver($loop));
    }
}
