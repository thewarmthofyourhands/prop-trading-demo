<?php

declare(strict_types=1);

namespace App\RequestService;

use Eva\Http\HttpMethodsEnum;

readonly class CapitalRequestService
{
    private const BASE_URL = 'https://demo-api-capital.backend-capital.com';

    public function getMarkets(array $params = ['epics' => 'BTCUSD']): array
    {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $url = self::BASE_URL . '/api/v1/markets';

        if ($params !== []) {
            $url .= '?' . http_build_query($params);
        }

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::GET,
            $url,
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $body);
    }

    public function getMarketPrices(
        string $epic = 'BTCUSD',
        string $resolution = 'MINUTE',
        int $max = 100,
    ): array {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $url = self::BASE_URL . '/api/v1/prices/' . rawurlencode($epic)
            . '?' . http_build_query([
                'resolution' => $resolution,
                'max' => $max,
            ]);

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::GET,
            $url,
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $body);
    }

    public function getPositions(): array
    {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::GET,
            self::BASE_URL . '/api/v1/positions',
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $body);
    }

    public function getPosition(string $dealId): array
    {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::GET,
            self::BASE_URL . '/api/v1/positions/' . rawurlencode($dealId),
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $body);
    }

    public function getConfirm(string $dealReference): array
    {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::GET,
            self::BASE_URL . '/api/v1/confirms/' . rawurlencode($dealReference),
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $body);
    }

    public function createPosition(
        string $epic,
        string $direction,
        float $size,
    ): array {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $body = json_encode([
            'epic' => $epic,
            'direction' => $direction,
            'size' => $size,
        ], JSON_THROW_ON_ERROR);

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::POST,
            self::BASE_URL . '/api/v1/positions',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
            $body,
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $responseBody = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $responseBody);
    }

    public function closePosition(string $dealId): array
    {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::DELETE,
            self::BASE_URL . '/api/v1/positions/' . rawurlencode($dealId),
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $body);
    }

    public function getHistoryForLastMonth(): array
    {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();

        $params = [
            'from' => (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
                ->modify('-1 month')
                ->format('Y-m-d\TH:i:s'),
            'max' => 100,
        ];

        $url = self::BASE_URL . '/api/v1/history/transactions'
            . '?' . http_build_query($params);

        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::GET,
            $url,
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($statusCode . ' ' . $body);
    }

    private function getTokens(): array
    {
        $apiKey = 'hNQWOcirlQJd5x7u';
        $identifier = 'rostislavpanteleychuk@gmail.com';
        $apiPassword = 'x87aNm!sZd@b@YP';
        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::POST,
            self::BASE_URL . '/api/v1/session',
            [
                'Content-Type' => 'application/json',
                'X-CAP-API-KEY' => $apiKey,
            ],
            json_encode([
                'identifier' => $identifier,
                'password' => $apiPassword,
                'encryptedPassword' => false,
            ], JSON_THROW_ON_ERROR),
        );

        $client = new \Eva\Http\Client();
        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = (string) $response->getBody();

        if ($statusCode !== 200) {
            throw new \RuntimeException(
                'Capital.com session creation failed: '
                . $statusCode . ' ' . $body,
            );
        }

        $headers = $response->getHeaders();

        $cst = $headers['CST'] ?? null;
        $securityToken = $headers['X-SECURITY-TOKEN'] ?? null;

        if (!is_string($cst) || !is_string($securityToken)) {
            throw new \RuntimeException(
                'Capital.com authentication tokens are missing.',
            );
        }

        return [
            'cst' => trim($cst),
            'securityToken' => trim($securityToken),
        ];
    }


    public function getAccounts(): array
    {
        $tokens = $this->getTokens();
        $client = new \Eva\Http\Client();
        $request = new \Eva\Http\Message\Request(
            HttpMethodsEnum::GET,
            self::BASE_URL . '/api/v1/accounts',
            [
                'Accept' => 'application/json',
                'CST' => $tokens['cst'],
                'X-SECURITY-TOKEN' => $tokens['securityToken'],
            ],
        );

        $response = $client->sendRequest($request);
        $statusCode = $response->getStatusCode();
        $body = $response->getBody();

        if ($statusCode === 200) {
            return json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        }

        throw new \RuntimeException($response->getStatusCode(). ' ' . $body);
    }
}
