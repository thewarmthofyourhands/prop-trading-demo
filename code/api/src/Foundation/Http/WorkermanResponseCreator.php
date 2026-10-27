<?php

declare(strict_types=1);

namespace App\Foundation\Http;

use Eva\Http\Message\ResponseInterface;
use Workerman\Protocols\Http\Response as WorkermanResponse;

class WorkermanResponseCreator
{
    public static function createWorkermanResponse(ResponseInterface $response): WorkermanResponse
    {
        return new WorkermanResponse(
            $response->getStatusCode(),
            $response->getHeaders(),
            $response->getBody(),
        );
    }
}
