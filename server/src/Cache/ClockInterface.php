<?php

declare(strict_types=1);

namespace App\Cache;

interface ClockInterface
{
    public function now(): int;
}
