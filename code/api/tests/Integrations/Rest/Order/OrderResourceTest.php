<?php

declare(strict_types=1);

namespace Tests\Integrations\Rest\Order;

use Eva\Http\HttpMethodsEnum;
use Eva\Http\Message\Request;
use Eva\Http\Message\Response;
use Tests\Integrations\ApiWithAuthTestCase;

class OrderResourceTest extends ApiWithAuthTestCase
{

    private function createOrder(): Response
    {
        $requestBody = file_get_contents('./var/tests/requests/Order/Store.json');
        $requestBody = json_encode(json_decode($requestBody));

        $request = new Request(
            HttpMethodsEnum::POST,
            '/api/orders',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'r4nd0mTok3nValue20',
            ],
            $requestBody,
        );
        return $this->application->handle($request);
    }

    public function testOrderStore(): void
    {
        $expectedResponse = file_get_contents('./var/tests/responses/Order/Store.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $response = $this->createOrder();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());
    }

    public function testOrderShow(): void
    {
        $this->createOrder();
        $expectedResponse = file_get_contents('./var/tests/responses/Order/Show.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $request = new Request(HttpMethodsEnum::GET, '/api/orders/1', [
            'Authorization' => 'r4nd0mTok3nValue20',
        ]);
        $response = $this->application->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());
    }

    public function testOrderPayment(): void
    {
        $this->createOrder();
        $expectedResponse = file_get_contents('./var/tests/responses/Order/Payment.json');
        $expectedResponse = json_encode(json_decode($expectedResponse));

        $requestBody = file_get_contents('./var/tests/requests/Order/Payment.json');
        $requestBody = json_encode(json_decode($requestBody));

        $request = new Request(
            HttpMethodsEnum::POST,
            '/api/orders/1/payment',
            [
                'Content-Type' => 'application/json',
                'Authorization' => 'r4nd0mTok3nValue20',
            ],
            $requestBody,
        );
        $response = $this->application->handle($request);

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame($expectedResponse, $response->getBody());

        $expectedPaidResponse = file_get_contents('./var/tests/responses/Order/ShowPaid.json');
        $expectedPaidResponse = json_encode(json_decode($expectedPaidResponse));

        $request = new Request(HttpMethodsEnum::GET, '/api/orders/1', [
                'Content-Type' => 'application/json',
                'Authorization' => 'r4nd0mTok3nValue20',
        ]);
        $response = $this->application->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame($expectedPaidResponse, $response->getBody());
    }
}
