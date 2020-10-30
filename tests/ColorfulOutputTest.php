<?php

namespace Vajexal\HotReload\Tests;

use Amp\File;
use Amp\Process\Process;

class ColorfulOutputTest extends TestCase
{
    public function colorfulOutputProvider()
    {
        return [
            [
                <<<'EOL'
<?php

echo "\033[32mhello\033[0m";
EOL,
                "\033[32mhello\033[0m",
            ],
            [
                <<<'EOL'
<?php

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Monolog\Logger;
use function Amp\ByteStream\getStdout;

require_once 'vendor/autoload.php';

$logHandler = new StreamHandler(getStdout());
$logHandler->setFormatter(new ConsoleFormatter);
$logger = new Logger('test');
$logger->pushHandler($logHandler);

$logger->debug('hello');
EOL,
                "\033[1;36mdebug\033[0m: hello",
            ],
            [
                <<<'EOL'
<?php

use Symfony\Component\Console\Output\ConsoleOutput;

require_once 'vendor/autoload.php';

$output = new ConsoleOutput();

$output->write('<info>hello</info>');
EOL,
                "\033[32mhello\033[39m",
            ],
            [
                <<<'EOL'
<?php

use Symfony\Component\Console\Output\ConsoleOutput;

require_once 'vendor/autoload.php';

$output = new ConsoleOutput();

$output->write('<info>hello</info>');
EOL,
                'hello',
                [
                    'NO_COLOR' => true,
                ],
            ],
        ];
    }

    /**
     * @dataProvider colorfulOutputProvider
     */
    public function testColorfulOutput(string $program, string $output, array $env = [])
    {
        $this->setTimeout(2000);

        yield File\put($this->filepath, $program);

        $process = new Process(['./hot-reload', $this->filepath], null, [
                'TERM_PROGRAM' => 'Hyper',
            ] + $env + \getenv());
        yield $process->start();

        $this->assertStringContainsString($output, yield $process->getStdout()->read());
    }
}
