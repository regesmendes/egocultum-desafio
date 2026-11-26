<?php

declare(strict_types=1);

namespace App\Weather;

interface WeatherProviderInterface
{
    /**
     * @return array{time: string[], temperature_2m_min: array<float|null>, temperature_2m_max: array<float|null>, precipitation_sum: array<float|null>}
     * @throws WeatherProviderException quando não for possível obter a previsão
     */
    public function getForecast(float $latitude, float $longitude): array;
}
