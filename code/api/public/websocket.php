<?php

declare(strict_types=1);

use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;
use RuntimeException;
use Eva\Http\HttpMethodsEnum;

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

$ws = new Worker('websocket://0.0.0.0:2346');
$ws->count = 1;

function getTokens(): array
{
    $baseUrl = 'https://demo-api-capital.backend-capital.com';
    $apiKey = 'hNQWOcirlQJd5x7u';
    $identifier = 'rostislavpanteleychuk@gmail.com';
    $apiPassword = 'x87aNm!sZd@b@YP';
    $request = new \Eva\Http\Message\Request(
        HttpMethodsEnum::POST,
        $baseUrl . '/api/v1/session',
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

$tokens = getTokens();
// exit;


// $ws->onWorkerStart = static function (Worker $worker) use (&$tokens): void {
//     $marketData = [];
//     $connect = null;

//     $connect = static function () use (&$connect, &$marketData, &$tokens, &$worker): void {
//         $capital = new AsyncTcpConnection(
//             'ws://api-streaming-capital.backend-capital.com/connect',
//         );
//         $capital->transport = 'ssl';

//         $capital->onConnect = static function (AsyncTcpConnection $connection) use (&$tokens, &$worker): void {
//             echo "Capital connected\n";

//             $connection->send(json_encode([
//                 'destination' => 'marketData.subscribe',
//                 'correlationId' => 'sub-1',
//                 'cst' => $tokens['cst'],
//                 'securityToken' => $tokens['securityToken'],
//                 'payload' => [
//                     'epics' => [
//                         'BTCUSD',
//                     ],
//                 ],
//             ], JSON_THROW_ON_ERROR));
//         };

//         $capital->onMessage = static function (
//             AsyncTcpConnection $connection,
//             string $message
//         ) use (&$marketData, &$worker): void {
//             try {
//                 $payload = json_decode(
//                     $message,
//                     true,
//                     512,
//                     JSON_THROW_ON_ERROR,
//                 );
//             } catch (\JsonException) {
//                 return;
//             }

//             if (
//                 ($payload['status'] ?? null) !== 'OK'
//                 || ($payload['destination'] ?? null) !== 'quote'
//             ) {
//                 echo "Capital: {$message}\n";
//                 return;
//             }

//             $quote = $payload['payload'] ?? [];
//             $epic = $quote['epic'] ?? null;

//             if (!is_string($epic)) {
//                 return;
//             }

//             $marketData[$epic] = [
//                 'bid' => $quote['bid'] ?? null,
//                 'ask' => $quote['ofr'] ?? null,
//                 'bidQty' => $quote['bidQty'] ?? null,
//                 'askQty' => $quote['ofrQty'] ?? null,
//                 'timestamp' => $quote['timestamp'] ?? null,
//             ];

//             $message = json_encode([
//                 'type' => 'market',
//                 'time' => time(),
//                 'data' => $marketData,
//             ], JSON_THROW_ON_ERROR);

//             foreach ($worker->connections as $workerConnection) {
//                 $workerConnection->send($message);
//             }
//         };

//         $capital->onClose = static function () use (&$connect): void {
//             echo "Capital disconnected\n";

//             Timer::add(3, static function () use (&$connect): void {
//                 $connect();
//             }, [], false);
//         };

//         $capital->onError = static function (
//             AsyncTcpConnection $connection,
//             int $code,
//             string $msg
//         ): void {
//             echo "Capital error {$code}: {$msg}\n";
//         };

//         $capital->connect();
//     };

//     $connect();
// };


$ws->onWorkerStart = static function (Worker $worker): void {
    $marketData = [];
    $capital = null;
    $reconnecting = false;

    $connect = null;

    $connect = static function () use (
        &$connect,
        &$capital,
        &$marketData,
        &$reconnecting,
        &$worker,
    ): void {
        if ($reconnecting) {
            return;
        }

        $reconnecting = true;

        // Закрываем предыдущее соединение.
        if ($capital instanceof AsyncTcpConnection) {
            $oldCapital = $capital;
            $capital = null;

            $oldCapital->onClose = null;
            $oldCapital->onError = null;
            $oldCapital->onMessage = null;
            $oldCapital->close();
        }

        try {
            // Получаем свежие токены.
            $tokens = getTokens();

            $connection = new AsyncTcpConnection(
                'ws://api-streaming-capital.backend-capital.com/connect',
            );

            $connection->transport = 'ssl';
            $capital = $connection;

            $connection->onConnect = static function (
                AsyncTcpConnection $connection
            ) use (&$tokens, &$reconnecting): void {
                echo "Capital connected\n";

                $reconnecting = false;

                $connection->send(json_encode([
                    'destination' => 'marketData.subscribe',
                    'correlationId' => 'sub-1',
                    'cst' => $tokens['cst'],
                    'securityToken' => $tokens['securityToken'],
                    'payload' => [
                        'epics' => ['BTCUSD'],
                    ],
                ], JSON_THROW_ON_ERROR));
            };

            $connection->onMessage = static function (
                AsyncTcpConnection $connection,
                string $message
            ) use (&$marketData, &$worker): void {
                try {
                    $payload = json_decode(
                        $message,
                        true,
                        512,
                        JSON_THROW_ON_ERROR,
                    );
                } catch (\JsonException) {
                    return;
                }

                if (
                    ($payload['status'] ?? null) !== 'OK'
                    || ($payload['destination'] ?? null) !== 'quote'
                ) {
                    echo "Capital: {$message}\n";
                    return;
                }

                $quote = $payload['payload'] ?? [];
                $epic = $quote['epic'] ?? null;

                if (!is_string($epic)) {
                    return;
                }

                $marketData[$epic] = [
                    'bid' => $quote['bid'] ?? null,
                    'ask' => $quote['ofr'] ?? null,
                    'bidQty' => $quote['bidQty'] ?? null,
                    'askQty' => $quote['ofrQty'] ?? null,
                    'timestamp' => $quote['timestamp'] ?? null,
                ];

                $outgoingMessage = json_encode([
                    'type' => 'market',
                    'time' => time(),
                    'data' => $marketData,
                ], JSON_THROW_ON_ERROR);

                foreach ($worker->connections as $workerConnection) {
                    $workerConnection->send($outgoingMessage);
                }
            };

            $connection->onClose = static function () use (
                &$connect,
                &$capital,
                &$reconnecting,
                $connection,
            ): void {
                echo "Capital disconnected\n";

                if ($capital !== $connection) {
                    return;
                }

                $capital = null;
                $reconnecting = false;

                Timer::add(3, static function () use (&$connect): void {
                    $connect();
                }, [], false);
            };

            $connection->onError = static function (
                AsyncTcpConnection $connection,
                int $code,
                string $msg
            ): void {
                echo "Capital error {$code}: {$msg}\n";
            };

            $connection->connect();
        } catch (\Throwable $exception) {
            $reconnecting = false;

            echo "Capital reconnect error: {$exception->getMessage()}\n";

            Timer::add(3, static function () use (&$connect): void {
                $connect();
            }, [], false);
        }
    };

    // Первоначальное подключение.
    $connect();

    // Обновляем токены и пересоздаём соединение каждые 5 минут.
    Timer::add(300, static function () use (&$connect): void {
        echo "Capital token rotation\n";
        $connect();
    });
};


$ws->onConnect = static function (TcpConnection $connection): void {
    $connection->send(json_encode([
        'type' => 'welcome',
        'message' => 'WebSocket connected',
    ], JSON_THROW_ON_ERROR));
};

$ws->onError = static function (
    TcpConnection $connection,
    int $code,
    string $msg
): void {
    error_log("WebSocket error {$code}: {$msg}");
};

$ws->onClose = static function (TcpConnection $connection): void {
};

Worker::runAll();