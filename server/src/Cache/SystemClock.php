<?php

declare(strict_types=1);

namespace App\Cache;

final class SystemClock implements ClockInterface
{
    public function now(): int
    {
        return time();
    }
}
