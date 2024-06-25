<?php

declare(strict_types=1);

namespace ReactParallel\Pool\Test;

use Closure;
use React\EventLoop\Loop;
use ReactParallel\Contracts\ClosedException;
use ReactParallel\Contracts\GroupInterface;
use ReactParallel\Contracts\LowLevelPoolInterface;
use ReactParallel\EventLoop\KilledRuntime;
use WyriHaximus\Metrics\Label;
use WyriHaximus\PoolInfo\Info;

use function count;
use function React\Async\await;
use function Safe\hrtime;
use function WyriHaximus\React\futurePromise;

use const WyriHaximus\Constants\Boolean\FALSE_;
use const WyriHaximus\Constants\Boolean\TRUE_;

final class Direct implements LowLevelPoolInterface
{
    private Metrics|null $metrics = null;

    /** @var GroupInterface[] */
    private array $groups = [];

    private bool $closed = FALSE_;
    private bool $killed = FALSE_;

    private int $running = 0;
    private int $idle    = 0;

    public function withMetrics(Metrics $metrics): self
    {
        $self          = clone $this;
        $self->metrics = $metrics;

        return $self;
    }

    /**
     * @param (Closure():T) $callable
     * @param array<mixed>  $args
     *
     * @return T
     *
     * @template T
     */
    public function run(Closure $callable, array $args = []): mixed
    {
        if ($this->closed === TRUE_) {
            throw ClosedException::create();
        }

        $time = null;
        if ($this->metrics instanceof Metrics) {
            $this->metrics->threads()->gauge(new Label('state', 'busy'))->incr();
            $this->metrics->threads()->gauge(new Label('state', 'idle'))->dcr();
            $time = hrtime(true);
        }

        try {
            $this->running++;
            await(futurePromise());
            if ($this->killed === TRUE_) {
                throw new KilledRuntime();
            }

            /** @psalm-suppress TooManyArguments */
            $result =  $callable(...$args);
            $this->idle++;
            Loop::futureTick(function (): void {
                $this->idle--;
                $this->running--;
            });

            return $result;
        } finally {
            if ($this->metrics instanceof Metrics) {
                /**
                 * @psalm-suppress PossiblyInvalidOperand
                 * @psalm-suppress PossiblyNullOperand
                 */
                $this->metrics->executionTime()->summary()->observe((hrtime(true) - $time) / 1e+9); /** @phpstan-ignore-line */
                $this->metrics->threads()->gauge(new Label('state', 'idle'))->incr();
                $this->metrics->threads()->gauge(new Label('state', 'busy'))->dcr();
            }
        }
    }

    public function close(): bool
    {
        if (count($this->groups) > 0) {
            return FALSE_;
        }

        $this->closed = TRUE_;

        return TRUE_;
    }

    public function kill(): bool
    {
        if (count($this->groups) > 0) {
            return FALSE_;
        }

        $this->closed = TRUE_;
        $this->killed = TRUE_;

        return TRUE_;
    }

    /** @return iterable<string, int> */
    public function info(): iterable
    {
        yield Info::TOTAL => $this->running;
        yield Info::BUSY => $this->running - $this->idle;
        yield Info::CALLS => 0;
        yield Info::IDLE  => $this->idle;
        yield Info::SIZE  => $this->running;
    }

    public function acquireGroup(): GroupInterface
    {
        $group                         = Group::create();
        $this->groups[(string) $group] = $group;

        return $group;
    }

    public function releaseGroup(GroupInterface $group): void
    {
        unset($this->groups[(string) $group]);
    }
}
