<?php

declare(strict_types=1);

namespace App;

final class ForecastResult
{
    public function __construct(
        public readonly array $daily,
        public readonly array $alerts,
        public readonly bool $stale,
        public readonly ?string $error,
    ) {
    }
}
