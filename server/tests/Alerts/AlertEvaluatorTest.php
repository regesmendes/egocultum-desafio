<?php

declare(strict_types=1);

namespace Tests\Alerts;

use App\Alerts\AlertEvaluator;
use App\Alerts\AlertRuleInterface;
use PHPUnit\Framework\TestCase;

final class AlertEvaluatorTest extends TestCase
{
    public function test_agrega_os_alertas_de_todas_as_regras_pela_ordem_das_regras(): void
    {
        $ruleA = $this->createStub(AlertRuleInterface::class);
        $ruleA->method('evaluate')->willReturn([
            ['type' => 'frost', 'start' => '2026-12-01', 'end' => '2026-12-01'],
        ]);

        $ruleB = $this->createStub(AlertRuleInterface::class);
        $ruleB->method('evaluate')->willReturn([
            ['type' => 'heavy_rain', 'start' => '2026-12-02', 'end' => '2026-12-02'],
        ]);

        $evaluator = new AlertEvaluator([$ruleA, $ruleB]);

        $this->assertSame(
            [
                ['type' => 'frost', 'start' => '2026-12-01', 'end' => '2026-12-01'],
                ['type' => 'heavy_rain', 'start' => '2026-12-02', 'end' => '2026-12-02'],
            ],
            $evaluator->evaluate(['time' => []])
        );
    }

    public function test_sem_regras_devolve_lista_vazia(): void
    {
        $evaluator = new AlertEvaluator([]);

        $this->assertSame([], $evaluator->evaluate(['time' => []]));
    }
}
