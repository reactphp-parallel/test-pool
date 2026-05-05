<?php

declare(strict_types=1);

use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Direct;

use function React\Promise\all;

$json = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'large.json');

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$infinite = new Direct($loop, new EventLoopBridge($loop), 1);

$promises      = [];
$signalHandler = static function () use ($infinite, $loop): void {
    $loop->stop();
    $infinite->close();
};

$tick = static function () use (&$promises, $infinite, $loop, $signalHandler, $json, &$tick): void {
    if (count($promises) < 1000) {
        $promises[] = $infinite->run(static function ($json) {
            $json = json_decode($json, true);

            return md5(json_encode($json));
        }, [$json]);
        $loop->futureTick($tick);

        return;
    }

    all($promises)->then(static function ($v): void {
        var_export($v);
    })->always(static function () use ($infinite, $loop, $signalHandler): void {
        $infinite->close();
        $loop->removeSignal(SIGINT, $signalHandler);
        $loop->stop();
    })->done();
};
$loop->futureTick($tick);

$loop->addSignal(SIGINT, $signalHandler);

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
