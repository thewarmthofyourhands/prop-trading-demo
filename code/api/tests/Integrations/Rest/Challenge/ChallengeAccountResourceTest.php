<?php

declare(strict_types=1);

namespace Tests\Integrations\Rest\Challenge;

use Eva\Http\HttpMethodsEnum;
use Eva\Http\Message\Request;
use Tests\Integrations\ApiWithAccountCabinetTestCase;

class ChallengeAccountResourceTest extends ApiWithAccountCabinetTestCase
{
    public function testChallengeAccountIndex(): void
    {
        $expectedResponse = file_get_contents('./var/tests/responses/ChallengeAccount/Index.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $request = new Request(HttpMethodsEnum::GET, '/api/challenge-accounts', $this->getAuthHeaders());
        $response = $this->application->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());
    }

    public function testChallengeAccountShow(): void
    {
        $expectedResponse = file_get_contents('./var/tests/responses/ChallengeAccount/Show.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $request = new Request(HttpMethodsEnum::GET, '/api/challenge-accounts/1', $this->getAuthHeaders());
        $response = $this->application->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());
    }

    public function testChallengeAccountActivate(): void
    {
        $expectedResponse = file_get_contents('./var/tests/responses/ChallengeAccount/Activate.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $request = new Request(HttpMethodsEnum::POST, '/api/challenge-accounts/1/activate', $this->getAuthHeaders());
        $response = $this->application->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());
    }
}
