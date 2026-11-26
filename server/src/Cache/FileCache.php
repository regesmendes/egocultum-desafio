<?php

declare(strict_types=1);

namespace App\Cache;

final class FileCache implements CacheInterface
{
    public function __construct(
        private readonly string $directory,
        private readonly int $ttlSeconds,
        private readonly ClockInterface $clock,
    ) {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, recursive: true);
        }
    }

    public function get(string $key): mixed
    {
        $entry = $this->readEntry($key);
        if ($entry === null) {
            return null;
        }

        if ($this->clock->now() - $entry['storedAt'] >= $this->ttlSeconds) {
            return null;
        }

        return $entry['value'];
    }

    public function getStale(string $key): mixed
    {
        return $this->readEntry($key)['value'] ?? null;
    }

    public function set(string $key, mixed $value): void
    {
        $entry = ['storedAt' => $this->clock->now(), 'value' => $value];
        $path = $this->pathFor($key);

        // Escreve para um ficheiro temporário e troca com rename() (atómico no
        // mesmo sistema de ficheiros), em vez de truncar o ficheiro final
        // diretamente — uma escrita falhada a meio não destrói a última
        // resposta válida, que serve de fallback quando a origem falha.
        $temporaryPath = $path . '.' . uniqid('', more_entropy: true) . '.tmp';
        file_put_contents($temporaryPath, json_encode($entry));
        rename($temporaryPath, $path);
    }

    private function readEntry(string $key): ?array
    {
        $path = $this->pathFor($key);
        if (!is_file($path)) {
            return null;
        }

        $entry = json_decode(file_get_contents($path), true);
        if (!is_array($entry) || !array_key_exists('storedAt', $entry) || !array_key_exists('value', $entry)) {
            return null;
        }

        return $entry;
    }

    private function pathFor(string $key): string
    {
        return $this->directory . '/' . hash('sha256', $key) . '.json';
    }
}
