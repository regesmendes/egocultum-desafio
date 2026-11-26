#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Alerts\AlertEvaluator;
use App\Alerts\DroughtAlertRule;
use App\Alerts\FrostAlertRule;
use App\Alerts\HeavyRainAlertRule;

$path = $argv[1] ?? null;
if ($path === null) {
    fwrite(STDERR, "Uso: php bin/alertas.php <ficheiro.json>\n");
    exit(1);
}

if (!is_file($path)) {
    fwrite(STDERR, "Ficheiro não encontrado: {$path}\n");
    exit(1);
}

$decoded = json_decode((string) file_get_contents($path), true);
if (!is_array($decoded) || !isset($decoded['daily']) || !is_array($decoded['daily'])) {
    fwrite(STDERR, "Ficheiro inválido: esperado um JSON com a chave \"daily\" (formato da resposta da Open-Meteo).\n");
    exit(1);
}

$evaluator = new AlertEvaluator([
    new FrostAlertRule(),
    new HeavyRainAlertRule(),
    new DroughtAlertRule(),
]);

$alerts = $evaluator->evaluate($decoded['daily']);

if ($alerts === []) {
    echo "Sem alertas.\n";
    exit(0);
}

$labels = [
    'frost' => 'Geada',
    'heavy_rain' => 'Chuva forte',
    'drought' => 'Seca',
];

function formatarData(string $isoDate): string
{
    [$ano, $mes, $dia] = explode('-', $isoDate);

    return "{$dia}/{$mes}/{$ano}";
}

foreach ($alerts as $alert) {
    $label = $labels[$alert['type']] ?? $alert['type'];
    $inicio = formatarData($alert['start']);
    $fim = formatarData($alert['end']);

    echo $alert['start'] === $alert['end']
        ? "{$label}: {$inicio}\n"
        : "{$label}: {$inicio} a {$fim}\n";
}
