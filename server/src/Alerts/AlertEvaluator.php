<?php

declare(strict_types=1);

namespace App\Alerts;

final class AlertEvaluator
{
    /** @param AlertRuleInterface[] $rules */
    public function __construct(private readonly array $rules)
    {
    }

    public function evaluate(array $dailyData): array
    {
        $alerts = [];

        foreach ($this->rules as $rule) {
            $alerts = [...$alerts, ...$rule->evaluate($dailyData)];
        }

        return $alerts;
    }
}
