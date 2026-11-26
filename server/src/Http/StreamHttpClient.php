<?php

declare(strict_types=1);

namespace App\Http;

final class StreamHttpClient implements HttpClientInterface
{
    public function __construct(private readonly int $timeoutSeconds = 5)
    {
    }

    public function get(string $url): HttpResponse
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => $this->timeoutSeconds,
                'ignore_errors' => true,
            ],
        ]);

        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new HttpTransportException("Falha de rede ao contactar {$url}.");
        }

        return new HttpResponse($this->extractStatusCode($http_response_header ?? []), $body);
    }

    private function extractStatusCode(array $headers): int
    {
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                return (int) $matches[1];
            }
        }

        return 0;
    }
}
