<?php

declare(strict_types=1);

namespace Tests\Integrations\Rest\User;

use Eva\Http\HttpMethodsEnum;
use Eva\Http\Message\Request;
use Tests\Integrations\ApiTestCase;

class UserResourceTest extends ApiTestCase
{
    public function testUserStore(): void
    {
        $expectedResponse = file_get_contents('./var/tests/responses/User/Store.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $requestBody = file_get_contents('./var/tests/requests/User/Store.json');
        $requestBody = json_encode(json_decode($requestBody));

        $request = new Request(
            HttpMethodsEnum::POST,
            '/api/users',
            ['Content-Type' => 'application/json'],
            $requestBody,
        );
        $response = $this->application->handle($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());
    }
}
