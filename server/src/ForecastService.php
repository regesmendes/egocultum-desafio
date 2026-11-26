<?php

declare(strict_types=1);

namespace App;

use App\Alerts\AlertEvaluator;
use App\Cache\CacheInterface;
use App\Weather\WeatherProviderException;
use App\Weather\WeatherProviderInterface;

final class ForecastService
{
    public function __construct(
        private readonly WeatherProviderInterface $weatherProvider,
        private readonly CacheInterface $cache,
        private readonly AlertEvaluator $alertEvaluator,
    ) {
    }

    public function getForecast(float $latitude, float $longitude): ForecastResult
    {
        $key = $this->cacheKey($latitude, $longitude);

        $daily = $this->cache->get($key);
        if ($daily !== null) {
            return $this->success($daily, stale: false, error: null);
        }

        try {
            $daily = $this->weatherProvider->getForecast($latitude, $longitude);
        } catch (WeatherProviderException) {
            return $this->fallbackAfterFailure($key);
        }

        $this->cache->set($key, $daily);

        return $this->success($daily, stale: false, error: null);
    }

    private function fallbackAfterFailure(string $key): ForecastResult
    {
        $stale = $this->cache->getStale($key);
        if ($stale !== null) {
            return $this->success(
                $stale,
                stale: true,
                error: 'Não foi possível atualizar a previsão agora; a mostrar os últimos dados conhecidos.',
            );
        }

        return new ForecastResult(
            daily: [],
            alerts: [],
            stale: false,
            error: 'Não foi possível obter a previsão meteorológica agora. Tente novamente mais tarde.',
        );
    }

    private function success(array $daily, bool $stale, ?string $error): ForecastResult
    {
        return new ForecastResult($daily, $this->alertEvaluator->evaluate($daily), $stale, $error);
    }

    private function cacheKey(float $latitude, float $longitude): string
    {
        return sprintf('%.2F,%.2F', $latitude, $longitude);
    }
}
