<?php

declare(strict_types=1);

namespace App\Cache;

interface CacheInterface
{
    /**
     * Devolve o valor guardado para a chave, ou null se não existir ou já tiver expirado.
     */
    public function get(string $key): mixed;

    /**
     * Devolve o valor guardado para a chave independentemente de ter expirado, ou null
     * se nunca tiver sido guardado. Usado como último recurso quando a origem dos
     * dados falha e uma resposta desatualizada é preferível a nenhuma resposta.
     */
    public function getStale(string $key): mixed;

    public function set(string $key, mixed $value): void;
}
