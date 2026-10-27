<?php

declare(strict_types=1);

namespace App\Infrastructure\Rest;

use Eva\Http\Message\Response;

class BinaryResponse
{
    public function __construct(
        private readonly string $data = '',
        private readonly array $headers = [
        ],
    ) {}

    public function build(int $httpCode = 200): Response
    {
        return new Response(
            $httpCode,
            $this->headers,
            $this->data,
        );
    }
}
