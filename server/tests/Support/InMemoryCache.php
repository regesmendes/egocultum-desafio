<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Cache\CacheInterface;

final class InMemoryCache implements CacheInterface
{
    private array $fresh = [];
    private array $stale = [];

    public function get(string $key): mixed
    {
        return $this->fresh[$key] ?? null;
    }

    public function getStale(string $key): mixed
    {
        return $this->stale[$key] ?? $this->fresh[$key] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $this->fresh[$key] = $value;
        $this->stale[$key] = $value;
    }

    /**
     * Simula uma entrada que já expirou (continua a existir para getStale(), mas não para get()).
     */
    public function seedStaleOnly(string $key, mixed $value): void
    {
        $this->stale[$key] = $value;
    }
}
