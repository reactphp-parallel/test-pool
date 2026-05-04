<?php

declare(strict_types=1);

use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Direct;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop     = Factory::create();
$infinite = new Direct($loop, new EventLoopBridge($loop), 1);
$infinite->run(static function () {
    sleep(1);

    return 'Hoi!';
})->then(static function (string $message) use ($infinite, $loop): void {
    echo $message, PHP_EOL;
    $infinite->close();
    $loop->stop();
});

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
