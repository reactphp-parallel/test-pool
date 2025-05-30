<?php

declare(strict_types=1);

namespace ReactParallel\Pool\Test;

use WyriHaximus\Metrics\Factory as MetricsFactory;
use WyriHaximus\Metrics\Label\Name;
use WyriHaximus\Metrics\Registry;

final readonly class Metrics
{
    public function __construct(
        private Registry\Gauges $threads,
        private Registry\Summaries $executionTime,
    ) {
    }

    public static function create(Registry $registry): self
    {
        return new self(
            $registry->gauge(
                'react_parallel_pool_direct_threads',
                'Currently active or idle thread count',
                new Name('state'),
            ),
            $registry->summary(
                'react_parallel_pool_direct_execution_time',
                'Thread call execution time',
                MetricsFactory::defaultQuantiles(),
            ),
        );
    }

    public function threads(): Registry\Gauges
    {
        return $this->threads;
    }

    public function executionTime(): Registry\Summaries
    {
        return $this->executionTime;
    }
}
