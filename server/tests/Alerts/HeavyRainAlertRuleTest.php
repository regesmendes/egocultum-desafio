<?php

declare(strict_types=1);

namespace Tests\Alerts;

use App\Alerts\HeavyRainAlertRule;
use PHPUnit\Framework\TestCase;

final class HeavyRainAlertRuleTest extends TestCase
{
    private HeavyRainAlertRule $rule;

    protected function setUp(): void
    {
        $this->rule = new HeavyRainAlertRule();
    }

    public function test_gera_alerta_quando_precipitacao_ultrapassa_20mm(): void
    {
        $daily = [
            'time' => ['2026-12-01', '2026-12-02'],
            'precipitation_sum' => [22.4, 0.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [['type' => 'heavy_rain', 'start' => '2026-12-01', 'end' => '2026-12-01']],
            $alerts
        );
    }

    public function test_gera_alerta_no_limite_exato_de_20mm(): void
    {
        $daily = [
            'time' => ['2026-12-01'],
            'precipitation_sum' => [20.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertCount(1, $alerts);
        $this->assertSame('heavy_rain', $alerts[0]['type']);
    }

    public function test_nao_gera_alerta_abaixo_de_20mm(): void
    {
        $daily = [
            'time' => ['2026-12-01'],
            'precipitation_sum' => [19.9],
        ];

        $this->assertSame([], $this->rule->evaluate($daily));
    }

    public function test_valor_nulo_nunca_gera_alerta(): void
    {
        $daily = [
            'time' => ['2026-12-01', '2026-12-02'],
            'precipitation_sum' => [null, 25.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [['type' => 'heavy_rain', 'start' => '2026-12-02', 'end' => '2026-12-02']],
            $alerts
        );
    }
}
