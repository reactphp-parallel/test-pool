<?php

declare(strict_types=1);

namespace ReactParallel\Tests\Pool\Test;

use PHPUnit\Framework\Attributes\Test;
use ReactParallel\Contracts\PoolInterface;
use ReactParallel\Pool\Test\Direct;
use ReactParallel\Pool\Test\Metrics;
use ReactParallel\Tests\AbstractPoolTest;
use WyriHaximus\Metrics\Factory as MetricsFactory;
use WyriHaximus\PoolInfo\PoolInfoInterface;
use WyriHaximus\PoolInfo\PoolInfoTestTrait;

final class DirectTest extends AbstractPoolTest
{
    use PoolInfoTestTrait;

    /** @phpstan-ignore-next-line */
    private function poolFactory(): PoolInfoInterface
    {
        return (new Direct())->withMetrics(Metrics::create(MetricsFactory::create()));
    }

    protected function createPool(): PoolInterface
    {
        return (new Direct())->withMetrics(Metrics::create(MetricsFactory::create()));
    }

    #[Test]
    public function aquireLock(): void
    {
        $pool = (new Direct())->withMetrics(Metrics::create(MetricsFactory::create()));

        $group = $pool->acquireGroup();
        self::assertFalse($pool->close());
        self::assertFalse($pool->kill());

        $pool->releaseGroup($group);
        self::assertTrue($pool->close());
        self::assertTrue($pool->kill());
    }
}
