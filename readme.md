Attempt to make hot reloading for php scripts ([Amp](https://amphp.org) for example)

[![Build Status](https://github.com/vajexal/hot-reload/workflows/Build/badge.svg)](https://github.com/vajexal/hot-reload/actions)

### Installation

```bash
composer require vajexal/hot-reload:dev-master --dev
```

### Usage

Amp server for example

`server.php`
```php
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
```

```bash
./vendor/bin/hot-reload server.php
```

Now you can modify `server.php` and see changes without restarting the script

### Alternatives

- [facebook watchman](https://facebook.github.io/watchman/)
```bash
watchman watch $(pwd)
watchman -- trigger $(pwd) hot-reload '*.php' -- php server.php
```

- [entr](https://eradman.com/entrproject/)
```bash
ls *.php | entr -r php server.php
```

### Notes

- package use filesystem polling, so it will add some cpu usage
- `vendor`, `.idea`, `.git` dirs aren't watched
