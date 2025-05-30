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
use function hrtime;
use function React\Async\await;
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
     * @param (Closure():T)|(Closure(A0):T)|(Closure(A0,A1):T)|(Closure(A0,A1,A2):T)|(Closure(A0,A1,A2,A3):T)|(Closure(A0,A1,A2,A3,A4):T)|(Closure():void)|(Closure(A0):void)|(Closure(A0,A1):void)|(Closure(A0,A1,A2):void)|(Closure(A0,A1,A2,A3):void)|(Closure(A0,A1,A2,A3,A4):void) $callable
     * @param array{}|array{A0}|array{A0,A1}|array{A0,A1,A2}|array{A0,A1,A2,A3}|array{A0,A1,A2,A3,A4}                                                                                                                                                                                   $args
     *
     * @return (
     *      $callable is (Closure():T) ? T : (
     *          $callable is (Closure(A0):T) ? T : (
     *              $callable is (Closure(A0,A1):T) ? T : (
     *                  $callable is (Closure(A0,A1,A2):T) ? T : (
     *                      $callable is (Closure(A0,A1,A2,A3):T) ? T : (
     *                          $callable is (Closure(A0,A1,A2,A3,A4):T) ? T : null
     *                      )
     *                  )
     *              )
     *          )
     *      )
     * )
     *
     * @template T
     * @template A0 (any number of function arguments, see https://github.com/phpstan/phpstan/issues/8214)
     * @template A1
     * @template A2
     * @template A3
     * @template A4
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

            $result =  $callable(...$args);
            $this->idle++;
            Loop::futureTick(function (): void {
                $this->idle--;
                $this->running--;
            });

            return $result;
        } finally {
            if ($this->metrics instanceof Metrics) {
                $this->metrics->executionTime()->summary()->observe((hrtime(true) - $time) / 1e+9);
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
