<?php

declare(strict_types=1);

namespace App\Http;

interface HttpClientInterface
{
    /**
     * @throws HttpTransportException quando não for possível completar o pedido (rede, timeout)
     */
    public function get(string $url): HttpResponse;
}
