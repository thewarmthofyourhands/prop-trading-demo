<?php

declare(strict_types=1);

namespace App\Foundation\Http;

use Eva\Http\HttpMethodsEnum;
use Eva\Http\HttpProtocolVersionEnum;
use Eva\Http\Message\Request;
use Eva\Http\Message\RequestInterface;
use Workerman\Protocols\Http\Request as WorkermanRequest;

class WorkermanRequestCreator
{
    public static function createFromWorkermanRequest(WorkermanRequest $wRequest): RequestInterface
    {
        $body = $wRequest->rawBody();
        $body = $body === '' ? null : $body;

        return new Request(
            HttpMethodsEnum::from($wRequest->method()),
//            (string) $wRequest->path(),
            (string) $wRequest->uri(),
            $wRequest->header(),
            $body,
            HttpProtocolVersionEnum::from($wRequest->protocolVersion()),
        );
    }
}
