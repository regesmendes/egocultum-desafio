<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Http\HttpClientInterface;
use App\Http\HttpResponse;
use App\Http\HttpTransportException;

final class FakeHttpClient implements HttpClientInterface
{
    public ?string $requestedUrl = null;

    public function __construct(
        private readonly ?HttpResponse $response = null,
        private readonly ?HttpTransportException $exception = null,
    ) {
    }

    public function get(string $url): HttpResponse
    {
        $this->requestedUrl = $url;

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response;
    }
}
