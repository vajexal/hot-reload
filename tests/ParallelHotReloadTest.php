<?php

namespace Vajexal\HotReload\Tests;

use Amp\File;
use Amp\File\ParallelDriver;

class ParallelHotReloadTest extends HotReloadTest
{
    protected function setUp(): void
    {
        parent::setUp();

        File\filesystem(new ParallelDriver);
    }
}
