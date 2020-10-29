<?php

namespace Vajexal\HotReload\Tests;

use Amp\File;
use Amp\File\EioDriver;

class EioHotReloadTest extends HotReloadTest
{
    protected function setUp(): void
    {
        parent::setUp();

        if (!EioDriver::isSupported()) {
            $this->markTestSkipped('eio driver is not supported');
        }

        File\filesystem(new EioDriver);
    }
}
