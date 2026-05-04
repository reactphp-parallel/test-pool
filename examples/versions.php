<?php

declare(strict_types=1);

use Composer\InstalledVersions;
use React\EventLoop\Factory;
use ReactParallel\EventLoop\EventLoopBridge;
use ReactParallel\Pool\Infinite\Direct;

require dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$loop = Factory::create();

$finite = new Direct($loop, new EventLoopBridge($loop), 0.1);

$loop->addTimer(1, static function () use ($finite, $loop): void {
    $finite->kill();
    $loop->stop();
});
$finite->run(static function (): array {
    return array_merge(...array_map(static fn (string $package): array => [$package => InstalledVersions::getPrettyVersion($package)], InstalledVersions::getInstalledPackages()));
})->then(static function (array $versions): void {
    var_export($versions);
});

echo 'Loop::run()', PHP_EOL;
$loop->run();
echo 'Loop::done()', PHP_EOL;
