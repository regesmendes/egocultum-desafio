<?php

declare(strict_types=1);

namespace Tests;

use App\Alerts\AlertEvaluator;
use App\Alerts\FrostAlertRule;
use App\ForecastService;
use App\Weather\WeatherProviderException;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeWeatherProvider;
use Tests\Support\InMemoryCache;

final class ForecastServiceTest extends TestCase
{
    private const LAT = 38.71;
    private const LON = -9.14;

    private function dailyFixture(): array
    {
        return [
            'time' => ['2026-12-01', '2026-12-02'],
            'temperature_2m_min' => [-1.0, 5.0],
            'temperature_2m_max' => [10.0, 12.0],
            'precipitation_sum' => [0.0, 0.0],
        ];
    }

    public function test_usa_dados_em_cache_sem_chamar_o_provider(): void
    {
        $cache = new InMemoryCache();

        // Povoa a cache através de um primeiro pedido bem-sucedido.
        $provider = new FakeWeatherProvider(result: $this->dailyFixture());
        $service = new ForecastService($provider, $cache, new AlertEvaluator([new FrostAlertRule()]));
        $service->getForecast(self::LAT, self::LON);
        $this->assertSame(1, $provider->calls);

        // Segundo pedido para a mesma localização: não deve voltar a chamar o provider.
        $result = $service->getForecast(self::LAT, self::LON);

        $this->assertSame(1, $provider->calls);
        $this->assertSame($this->dailyFixture(), $result->daily);
        $this->assertFalse($result->stale);
        $this->assertNull($result->error);
        $this->assertSame(
            [['type' => 'frost', 'start' => '2026-12-01', 'end' => '2026-12-01']],
            $result->alerts
        );
    }

    public function test_chama_o_provider_e_guarda_em_cache_quando_nao_ha_dados_em_cache(): void
    {
        $cache = new InMemoryCache();
        $provider = new FakeWeatherProvider(result: $this->dailyFixture());
        $service = new ForecastService($provider, $cache, new AlertEvaluator([]));

        $result = $service->getForecast(self::LAT, self::LON);

        $this->assertSame(1, $provider->calls);
        $this->assertSame($this->dailyFixture(), $result->daily);
        $this->assertFalse($result->stale);
        $this->assertNull($result->error);
    }

    public function test_quando_o_provider_falha_e_ha_cache_desatualizada_usa_a_desatualizada(): void
    {
        $cache = new InMemoryCache();
        $cache->seedStaleOnly('38.71,-9.14', $this->dailyFixture());
        $provider = new FakeWeatherProvider(exception: new WeatherProviderException('timeout'));
        $service = new ForecastService($provider, $cache, new AlertEvaluator([]));

        $result = $service->getForecast(self::LAT, self::LON);

        $this->assertSame(1, $provider->calls);
        $this->assertSame($this->dailyFixture(), $result->daily);
        $this->assertTrue($result->stale);
        $this->assertNotNull($result->error);
    }

    public function test_quando_o_provider_falha_sem_cache_devolve_erro_sem_dados(): void
    {
        $cache = new InMemoryCache();
        $provider = new FakeWeatherProvider(exception: new WeatherProviderException('timeout'));
        $service = new ForecastService($provider, $cache, new AlertEvaluator([]));

        $result = $service->getForecast(self::LAT, self::LON);

        $this->assertSame([], $result->daily);
        $this->assertSame([], $result->alerts);
        $this->assertFalse($result->stale);
        $this->assertNotNull($result->error);
    }

    public function test_coordenadas_proximas_arredondam_para_a_mesma_chave_de_cache(): void
    {
        $cache = new InMemoryCache();
        $provider = new FakeWeatherProvider(result: $this->dailyFixture());
        $service = new ForecastService($provider, $cache, new AlertEvaluator([]));

        $service->getForecast(38.7149, -9.1402);
        $service->getForecast(38.7103, -9.1366);

        $this->assertSame(1, $provider->calls);
    }
}
