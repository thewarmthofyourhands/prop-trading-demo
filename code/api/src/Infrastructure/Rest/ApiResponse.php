<?php

declare(strict_types=1);

namespace App\Infrastructure\Rest;

use App\Enum\Infrastructure\Rest\ApplicationSuccessCodeEnum;
use App\Enum\Infrastructure\Rest\Messages\ApplicationSuccessMessageEnum;
use Eva\Http\Message\Response;
use JsonException;

class ApiResponse
{
    private null|array $debug = null;

    public function __construct(
        private readonly null|array $data = null,
        private readonly int $code = ApplicationSuccessCodeEnum::DEFAULT->value,
        private readonly string $message = ApplicationSuccessMessageEnum::DEFAULT->value,
        private readonly array $headers = [
            'Content-Type' => 'application/json',
        ],
    ) {}

    public function setDebug(null|array $debug): static
    {
        $this->debug = $debug;

        return $this;
    }

    /**
     * @throws JsonException
     */
    public function build(int $httpCode = 200): Response
    {
        $data = [
            'data' => $this->data,
            'message' => $this->message,
            'code' => $this->code,
        ];
        null !== $this->debug && $data['debug'] = $this->debug;

        return new Response(
            $httpCode,
            $this->headers,
            json_encode($data, JSON_THROW_ON_ERROR),
        );
    }
}
