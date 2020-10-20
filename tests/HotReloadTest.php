<?php

namespace Vajexal\HotReload\Tests;

use Amp\Delayed;
use Amp\File;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\Request;
use Amp\PHPUnit\AsyncTestCase;
use Amp\Process\Process;

class HotReloadTest extends AsyncTestCase
{
    private string $dir;
    private string $filepath;

    protected function setUpAsync()
    {
        File\filesystem(new File\BlockingDriver);

        $this->dir      = __DIR__ . DIRECTORY_SEPARATOR . 'tmp';
        $this->filepath = $this->dir . DIRECTORY_SEPARATOR . 'temp.php';

        yield File\mkdir($this->dir);
    }

    protected function tearDownAsync()
    {
        yield File\unlink($this->filepath);
        yield File\rmdir($this->dir);
    }

    public function testEcho()
    {
        $this->setTimeout(10000);

        yield File\put(
            $this->filepath,
            <<<'EOL'
<?php

echo 'Hello World';
EOL
        );

        $process = new Process(['./hot-reload', $this->filepath]);
        yield $process->start();

        yield new Delayed(1000);

        $this->assertEquals('Hello World', yield $process->getStdout()->read());

        yield File\put(
            $this->filepath,
            <<<'EOL'
<?php

echo 'here we go';
EOL
        );

        yield new Delayed(1000);

        $this->assertEquals('here we go', yield $process->getStdout()->read());

        yield File\put(
            $this->filepath,
            <<<'EOL'
<?php

echo '1';

sleep(1);

echo '2';
EOL
        );

        yield new Delayed(1000);

        $this->assertEquals('1', yield $process->getStdout()->read());

        yield new Delayed(1000);

        $this->assertEquals('2', yield $process->getStdout()->read());

        yield File\put(
            $this->filepath,
            <<<'EOL'
<?php

throw new Exception;
EOL
        );

        yield new Delayed(1000);

        $this->assertStringContainsString('Fatal error: Uncaught Exception', yield $process->getStdout()->read());
    }

    public function testServer()
    {
        $this->setTimeout(10000);

        yield File\put(
            $this->filepath,
            <<<'EOL'
<?php

require_once 'vendor/autoload.php';

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Loop;
use Amp\Socket;
use Psr\Log\NullLogger;

Loop::run(static function () {
    $servers = [
        Socket\Server::listen('0.0.0.0:1337'),
    ];

    $server = new HttpServer($servers, new CallableRequestHandler(static function () {
        return new Response(Status::OK, [], 'Hello World');
    }), new NullLogger);

    yield $server->start();
});
EOL
        );

        $process = new Process(['./hot-reload', $this->filepath]);
        yield $process->start();

        yield new Delayed(1000);

        $client   = HttpClientBuilder::buildDefault();
        $response = yield $client->request(new Request('http://127.0.0.1:1337'));
        $this->assertEquals('Hello World', yield $response->getBody()->buffer());

        yield File\put(
            $this->filepath,
            <<<'EOL'
<?php

require_once 'vendor/autoload.php';

use Amp\Http\Server\HttpServer;
use Amp\Http\Server\RequestHandler\CallableRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Status;
use Amp\Loop;
use Amp\Socket;
use Psr\Log\NullLogger;

Loop::run(static function () {
    $servers = [
        Socket\Server::listen('0.0.0.0:1337'),
    ];

    $server = new HttpServer($servers, new CallableRequestHandler(static function () {
        return new Response(Status::OK, [], 'here we go');
    }), new NullLogger);

    yield $server->start();
});
EOL
        );

        yield new Delayed(2000);

        $response = yield $client->request(new Request('http://localhost:1337'));
        $this->assertEquals('here we go', yield $response->getBody()->buffer());

        // To kill child process of ./hot-reload
        $process->signal(SIGINT);
        yield $process->join();
    }
}
