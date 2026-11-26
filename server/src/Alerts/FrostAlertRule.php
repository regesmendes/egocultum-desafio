<?php

declare(strict_types=1);

namespace App\Alerts;

final class FrostAlertRule implements AlertRuleInterface
{
    private const THRESHOLD_CELSIUS = 0.0;

    public function evaluate(array $dailyData): array
    {
        $alerts = [];

        foreach ($dailyData['temperature_2m_min'] ?? [] as $index => $minTemperature) {
            if ($minTemperature === null || $minTemperature > self::THRESHOLD_CELSIUS) {
                continue;
            }

            $date = $dailyData['time'][$index];
            $alerts[] = ['type' => 'frost', 'start' => $date, 'end' => $date];
        }

        return $alerts;
    }
}
