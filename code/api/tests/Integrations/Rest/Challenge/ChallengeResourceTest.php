<?php

declare(strict_types=1);

namespace Tests\Integrations\Rest\Challenge;

use Eva\Http\HttpMethodsEnum;
use Eva\Http\Message\Request;
use Tests\Integrations\ApiTestCase;

class ChallengeResourceTest extends ApiTestCase
{
    public function testChallengeIndex(): void
    {
        $expectedResponse = file_get_contents('./var/tests/responses/Challenge/Index.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $request = new Request(HttpMethodsEnum::GET, '/api/challenges');
        $response = $this->application->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());
    }
}
