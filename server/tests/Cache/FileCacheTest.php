<?php

declare(strict_types=1);

namespace Tests\Cache;

use App\Cache\FileCache;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeClock;

final class FileCacheTest extends TestCase
{
    private string $directory;
    private FakeClock $clock;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/filecache-test-' . uniqid();
        $this->clock = new FakeClock(1000);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->directory)) {
            foreach (glob($this->directory . '/*') as $file) {
                unlink($file);
            }
            rmdir($this->directory);
        }
    }

    private function cache(int $ttlSeconds = 3600): FileCache
    {
        return new FileCache($this->directory, $ttlSeconds, $this->clock);
    }

    public function test_devolve_null_para_chave_nunca_guardada(): void
    {
        $this->assertNull($this->cache()->get('inexistente'));
    }

    public function test_cria_o_diretorio_de_cache_se_nao_existir(): void
    {
        $this->assertDirectoryDoesNotExist($this->directory);

        $this->cache();

        $this->assertDirectoryExists($this->directory);
    }

    public function test_devolve_o_valor_guardado_enquanto_estiver_dentro_do_ttl(): void
    {
        $cache = $this->cache(ttlSeconds: 3600);
        $cache->set('lisboa', ['temperatura' => 18.5]);

        $this->clock->advance(3599);

        $this->assertSame(['temperatura' => 18.5], $cache->get('lisboa'));
    }

    public function test_devolve_null_depois_do_ttl_expirar(): void
    {
        $cache = $this->cache(ttlSeconds: 3600);
        $cache->set('lisboa', ['temperatura' => 18.5]);

        $this->clock->advance(3600);

        $this->assertNull($cache->get('lisboa'));
    }

    public function test_get_stale_devolve_o_valor_mesmo_depois_do_ttl_expirar(): void
    {
        $cache = $this->cache(ttlSeconds: 3600);
        $cache->set('lisboa', ['temperatura' => 18.5]);

        $this->clock->advance(10_000);

        $this->assertSame(['temperatura' => 18.5], $cache->getStale('lisboa'));
    }

    public function test_get_stale_devolve_null_se_nunca_guardado(): void
    {
        $this->assertNull($this->cache()->getStale('inexistente'));
    }

    public function test_chaves_diferentes_nao_colidem(): void
    {
        $cache = $this->cache();
        $cache->set('lisboa', 'dados-lisboa');
        $cache->set('porto', 'dados-porto');

        $this->assertSame('dados-lisboa', $cache->get('lisboa'));
        $this->assertSame('dados-porto', $cache->get('porto'));
    }

    public function test_guardar_de_novo_substitui_o_valor_e_reinicia_o_ttl(): void
    {
        $cache = $this->cache(ttlSeconds: 3600);
        $cache->set('lisboa', 'primeiro');

        $this->clock->advance(3000);
        $cache->set('lisboa', 'segundo');
        $this->clock->advance(3000);

        $this->assertSame('segundo', $cache->get('lisboa'));
    }
}
