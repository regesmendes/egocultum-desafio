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
        // Quando o stream wrapper segue um redirecionamento, $headers contém uma
        // linha "HTTP/..." por cada salto, pela ordem em que aconteceram — o
        // último valor é o código de estado da resposta final.
        $statusCode = 0;

        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                $statusCode = (int) $matches[1];
            }
        }

        return $statusCode;
    }
}
