<?php

declare(strict_types=1);

namespace Tests\Alerts;

use App\Alerts\DroughtAlertRule;
use PHPUnit\Framework\TestCase;

final class DroughtAlertRuleTest extends TestCase
{
    private DroughtAlertRule $rule;

    protected function setUp(): void
    {
        $this->rule = new DroughtAlertRule();
    }

    private function days(int $count, string $startDate = '2026-12-01'): array
    {
        $time = [];
        $start = new \DateTimeImmutable($startDate);
        for ($i = 0; $i < $count; $i++) {
            $time[] = $start->modify("+{$i} days")->format('Y-m-d');
        }

        return $time;
    }

    public function test_cinco_dias_seguidos_abaixo_de_1mm_gera_um_alerta(): void
    {
        $time = $this->days(5);
        $daily = [
            'time' => $time,
            'precipitation_sum' => [0.0, 0.2, 0.0, 0.0, 0.5],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [['type' => 'drought', 'start' => $time[0], 'end' => $time[4]]],
            $alerts
        );
    }

    public function test_quatro_dias_seguidos_nao_gera_alerta(): void
    {
        $daily = [
            'time' => $this->days(4),
            'precipitation_sum' => [0.0, 0.2, 0.0, 0.0],
        ];

        $this->assertSame([], $this->rule->evaluate($daily));
    }

    public function test_sequencia_maior_que_cinco_gera_um_unico_alerta_para_todo_o_periodo(): void
    {
        $time = $this->days(7);
        $daily = [
            'time' => $time,
            'precipitation_sum' => [0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [['type' => 'drought', 'start' => $time[0], 'end' => $time[6]]],
            $alerts
        );
    }

    public function test_valor_exatamente_1mm_nao_conta_como_dia_seco(): void
    {
        $daily = [
            'time' => $this->days(5),
            'precipitation_sum' => [0.0, 0.0, 1.0, 0.0, 0.0],
        ];

        $this->assertSame([], $this->rule->evaluate($daily));
    }

    public function test_valor_nulo_interrompe_o_periodo_e_nao_gera_alerta_para_sequencias_curtas(): void
    {
        $daily = [
            'time' => $this->days(7),
            'precipitation_sum' => [0.0, 0.0, 0.0, null, 0.0, 0.0, 0.0],
        ];

        $this->assertSame([], $this->rule->evaluate($daily));
    }

    public function test_duas_sequencias_separadas_geram_dois_alertas(): void
    {
        $time = $this->days(12);
        $daily = [
            'time' => $time,
            'precipitation_sum' => [
                0.0, 0.0, 0.0, 0.0, 0.0, // dias 0-4: seca (5 dias)
                25.0,                    // dia 5: chuva forte interrompe
                0.0, 0.0, 0.0, 0.0, 0.0, // dias 6-10: seca (5 dias)
                0.0,                     // dia 11: continuação da segunda seca
            ],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [
                ['type' => 'drought', 'start' => $time[0], 'end' => $time[4]],
                ['type' => 'drought', 'start' => $time[6], 'end' => $time[11]],
            ],
            $alerts
        );
    }

    public function test_sequencia_ainda_ativa_no_ultimo_dia_dos_dados_conta(): void
    {
        $time = $this->days(5);
        $daily = [
            'time' => $time,
            'precipitation_sum' => [1.5, 0.0, 0.0, 0.0, 0.0],
        ];

        $alerts = $this->rule->evaluate($daily);

        $this->assertSame(
            [['type' => 'drought', 'start' => $time[1], 'end' => $time[4]]],
            $alerts
        );
    }
}
