<?php

declare(strict_types=1);

namespace App\Alerts;

interface AlertRuleInterface
{
    /**
     * @param array{time: string[], temperature_2m_min?: array<float|null>, temperature_2m_max?: array<float|null>, precipitation_sum?: array<float|null>} $dailyData
     * @return list<array{type: string, start: string, end: string}>
     */
    public function evaluate(array $dailyData): array;
}
