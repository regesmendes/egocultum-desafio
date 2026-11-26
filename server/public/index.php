<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Alerts\AlertEvaluator;
use App\Alerts\DroughtAlertRule;
use App\Alerts\FrostAlertRule;
use App\Alerts\HeavyRainAlertRule;
use App\Cache\FileCache;
use App\Cache\SystemClock;
use App\ForecastService;
use App\Http\StreamHttpClient;
use App\Weather\OpenMeteoClient;

// A imagem php:8.5-cli não traz um php.ini, logo display_errors fica ligado por
// omissão: qualquer aviso (ex. escrita de cache falhada, resposta da API com
// formato inesperado) seria impresso em HTML antes do JSON, partindo a
// resposta e expondo caminhos do servidor ao cliente. Converte avisos em
// exceções e devolve sempre um JSON 500 limpo em caso de erro não tratado.
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function (\Throwable $exception): void {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Erro interno do servidor.']);
});

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

if ($path !== '/api/forecast') {
    http_response_code(404);
    echo json_encode(['error' => 'Rota não encontrada.']);
    exit;
}

$latitude = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$longitude = filter_input(INPUT_GET, 'lon', FILTER_VALIDATE_FLOAT);

if ($latitude === null || $latitude === false || $longitude === null || $longitude === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Parâmetros "lat" e "lon" são obrigatórios e devem ser numéricos.']);
    exit;
}

if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(400);
    echo json_encode(['error' => 'Latitude deve estar entre -90 e 90, longitude entre -180 e 180.']);
    exit;
}

$forecastService = new ForecastService(
    new OpenMeteoClient(new StreamHttpClient()),
    new FileCache(__DIR__ . '/../cache', ttlSeconds: 3600, clock: new SystemClock()),
    new AlertEvaluator([
        new FrostAlertRule(),
        new HeavyRainAlertRule(),
        new DroughtAlertRule(),
    ]),
);

$result = $forecastService->getForecast($latitude, $longitude);

echo json_encode([
    'daily' => $result->daily,
    'alerts' => $result->alerts,
    'stale' => $result->stale,
    'error' => $result->error,
]);
