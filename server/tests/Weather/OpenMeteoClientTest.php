<?php

declare(strict_types=1);

namespace Tests\Weather;

use App\Http\HttpResponse;
use App\Http\HttpTransportException;
use App\Weather\OpenMeteoClient;
use App\Weather\WeatherProviderException;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeHttpClient;

final class OpenMeteoClientTest extends TestCase
{
    private function successBody(): string
    {
        return json_encode([
            'daily' => [
                'time' => ['2026-12-01'],
                'temperature_2m_min' => [-1.0],
                'temperature_2m_max' => [10.0],
                'precipitation_sum' => [0.0],
            ],
        ]);
    }

    public function test_constroi_o_url_com_latitude_longitude_e_variaveis_diarias(): void
    {
        $httpClient = new FakeHttpClient(response: new HttpResponse(200, $this->successBody()));
        $client = new OpenMeteoClient($httpClient);

        $client->getForecast(38.71, -9.14);

        $this->assertNotNull($httpClient->requestedUrl);
        $this->assertStringStartsWith('https://api.open-meteo.com/v1/forecast?', $httpClient->requestedUrl);
        $this->assertStringContainsString('latitude=38.71', $httpClient->requestedUrl);
        $this->assertStringContainsString('longitude=-9.14', $httpClient->requestedUrl);
        $this->assertStringContainsString('temperature_2m_min', $httpClient->requestedUrl);
        $this->assertStringContainsString('precipitation_sum', $httpClient->requestedUrl);
    }

    public function test_devolve_os_dados_diarios_quando_a_resposta_e_valida(): void
    {
        $httpClient = new FakeHttpClient(response: new HttpResponse(200, $this->successBody()));
        $client = new OpenMeteoClient($httpClient);

        $daily = $client->getForecast(38.71, -9.14);

        $this->assertSame(['2026-12-01'], $daily['time']);
        $this->assertSame([-1.0], $daily['temperature_2m_min']);
    }

    public function test_lanca_excecao_quando_o_transporte_falha(): void
    {
        $httpClient = new FakeHttpClient(exception: new HttpTransportException('timeout'));
        $client = new OpenMeteoClient($httpClient);

        $this->expectException(WeatherProviderException::class);

        $client->getForecast(38.71, -9.14);
    }

    public function test_lanca_excecao_quando_o_codigo_http_nao_e_200(): void
    {
        $httpClient = new FakeHttpClient(response: new HttpResponse(500, 'erro interno'));
        $client = new OpenMeteoClient($httpClient);

        $this->expectException(WeatherProviderException::class);

        $client->getForecast(38.71, -9.14);
    }

    public function test_lanca_excecao_quando_o_corpo_nao_e_json_valido(): void
    {
        $httpClient = new FakeHttpClient(response: new HttpResponse(200, 'isto não é json'));
        $client = new OpenMeteoClient($httpClient);

        $this->expectException(WeatherProviderException::class);

        $client->getForecast(38.71, -9.14);
    }

    public function test_lanca_excecao_quando_falta_a_chave_daily(): void
    {
        $httpClient = new FakeHttpClient(response: new HttpResponse(200, json_encode(['latitude' => 38.71])));
        $client = new OpenMeteoClient($httpClient);

        $this->expectException(WeatherProviderException::class);

        $client->getForecast(38.71, -9.14);
    }
}
