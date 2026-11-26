<?php

declare(strict_types=1);

namespace App\Alerts;

final class HeavyRainAlertRule implements AlertRuleInterface
{
    private const THRESHOLD_MM = 20.0;

    public function evaluate(array $dailyData): array
    {
        $alerts = [];

        foreach ($dailyData['precipitation_sum'] ?? [] as $index => $precipitation) {
            if ($precipitation === null || $precipitation < self::THRESHOLD_MM) {
                continue;
            }

            $date = $dailyData['time'][$index];
            $alerts[] = ['type' => 'heavy_rain', 'start' => $date, 'end' => $date];
        }

        return $alerts;
    }
}
