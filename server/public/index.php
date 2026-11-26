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
