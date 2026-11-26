<?php

declare(strict_types=1);

namespace App\Alerts;

final class DroughtAlertRule implements AlertRuleInterface
{
    private const THRESHOLD_MM = 1.0;
    private const MIN_CONSECUTIVE_DAYS = 5;

    public function evaluate(array $dailyData): array
    {
        $time = $dailyData['time'];
        $precipitation = $dailyData['precipitation_sum'] ?? [];

        $alerts = [];
        $streakStart = null;

        foreach ($precipitation as $index => $value) {
            $isDryDay = $value !== null && $value < self::THRESHOLD_MM;

            if ($isDryDay) {
                $streakStart ??= $index;
                continue;
            }

            $alerts = $this->closeStreak($alerts, $time, $streakStart, $index - 1);
            $streakStart = null;
        }

        return $this->closeStreak($alerts, $time, $streakStart, count($precipitation) - 1);
    }

    private function closeStreak(array $alerts, array $time, ?int $startIndex, int $endIndex): array
    {
        if ($startIndex === null) {
            return $alerts;
        }

        if ($endIndex - $startIndex + 1 < self::MIN_CONSECUTIVE_DAYS) {
            return $alerts;
        }

        $alerts[] = ['type' => 'drought', 'start' => $time[$startIndex], 'end' => $time[$endIndex]];

        return $alerts;
    }
}
