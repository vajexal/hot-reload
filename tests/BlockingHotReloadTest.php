<?php

namespace Vajexal\HotReload\Tests;

use Amp\File;
use Amp\File\BlockingDriver;

class BlockingHotReloadTest extends HotReloadTest
{
    protected function setUp(): void
    {
        parent::setUp();

        File\filesystem(new BlockingDriver);
    }
}
