<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Weather\WeatherProviderException;
use App\Weather\WeatherProviderInterface;

final class FakeWeatherProvider implements WeatherProviderInterface
{
    public int $calls = 0;

    public function __construct(
        private readonly ?array $result = null,
        private readonly ?WeatherProviderException $exception = null,
    ) {
    }

    public function getForecast(float $latitude, float $longitude): array
    {
        $this->calls++;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->result;
    }
}
