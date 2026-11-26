<?php

declare(strict_types=1);

namespace App\Weather;

use App\Http\HttpClientInterface;
use App\Http\HttpTransportException;

final class OpenMeteoClient implements WeatherProviderInterface
{
    private const BASE_URL = 'https://api.open-meteo.com/v1/forecast';
    private const DAILY_VARIABLES = 'temperature_2m_min,temperature_2m_max,precipitation_sum';
    private const FORECAST_DAYS = 10;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $timezone = 'Europe/Lisbon',
    ) {
    }

    public function getForecast(float $latitude, float $longitude): array
    {
        $url = $this->buildUrl($latitude, $longitude);

        try {
            $response = $this->httpClient->get($url);
        } catch (HttpTransportException $exception) {
            throw new WeatherProviderException('Falha de rede ao contactar a Open-Meteo.', previous: $exception);
        }

        if ($response->statusCode !== 200) {
            throw new WeatherProviderException("A Open-Meteo respondeu com o código {$response->statusCode}.");
        }

        $decoded = json_decode($response->body, true);
        if (!is_array($decoded) || !isset($decoded['daily']) || !is_array($decoded['daily'])) {
            throw new WeatherProviderException('Resposta inválida da Open-Meteo.');
        }

        return $decoded['daily'];
    }

    private function buildUrl(float $latitude, float $longitude): string
    {
        return self::BASE_URL . '?' . http_build_query([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'daily' => self::DAILY_VARIABLES,
            'timezone' => $this->timezone,
            'forecast_days' => self::FORECAST_DAYS,
        ]);
    }
}
