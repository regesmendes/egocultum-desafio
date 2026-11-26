<?php

declare(strict_types=1);

namespace Tests\Alerts;

use App\Alerts\FrostAlertRule;
use PHPUnit\Framework\TestCase;

final class FrostAlertRuleTest extends TestCase
{
    private FrostAlertRule $rule;

    protected function setUp(): void
    {
        $this->rule = new FrostAlertRule();
    }

    public function test_gera_alerta_quando_minima_e_negativa(): void
    {
        $daily = [
            'time' => ['2026-12-01', '2026-12-02'],
            'temperature_2m_min' => [-1.2, 5.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [['type' => 'frost', 'start' => '2026-12-01', 'end' => '2026-12-01']],
            $alerts
        );
    }

    public function test_gera_alerta_no_limite_exato_de_zero_graus(): void
    {
        $daily = [
            'time' => ['2026-12-01'],
            'temperature_2m_min' => [0.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertCount(1, $alerts);
        $this->assertSame('frost', $alerts[0]['type']);
    }

    public function test_nao_gera_alerta_acima_de_zero_graus(): void
    {
        $daily = [
            'time' => ['2026-12-01'],
            'temperature_2m_min' => [0.1],
        ];

        $this->assertSame([], $this->rule->evaluate($daily));
    }

    public function test_valor_nulo_nunca_gera_alerta(): void
    {
        $daily = [
            'time' => ['2026-12-01', '2026-12-02'],
            'temperature_2m_min' => [null, -5.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [['type' => 'frost', 'start' => '2026-12-02', 'end' => '2026-12-02']],
            $alerts
        );
    }

    public function test_um_alerta_por_dia_gera_varios_alertas_independentes(): void
    {
        $daily = [
            'time' => ['2026-12-01', '2026-12-02', '2026-12-03'],
            'temperature_2m_min' => [-1.0, -2.0, -3.0],
        ];

        $this->assertCount(3, $this->rule->evaluate($daily));
    }
}
