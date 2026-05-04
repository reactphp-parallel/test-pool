<?php

declare(strict_types=1);

use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Direct;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$infinite = new Direct($loop, new EventLoopBridge($loop), 1);

$infinite->run(static function () {
    throw new RuntimeException('Whoops I did it again!');

    return 'We shouldn\'t reach this!';
})->always(static function () use ($infinite, $loop): void {
    $infinite->close();
    $loop->stop();
})->then(static function (string $oops): void {
    echo $oops, PHP_EOL;
}, static function (Throwable $error): void {
    echo $error, PHP_EOL;
})->done();

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
