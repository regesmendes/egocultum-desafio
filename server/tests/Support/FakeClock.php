<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Cache\ClockInterface;

final class FakeClock implements ClockInterface
{
    public function __construct(private int $currentTime = 0)
    {
    }

    public function now(): int
    {
        return $this->currentTime;
    }

    public function advance(int $seconds): void
    {
        $this->currentTime += $seconds;
    }
}
