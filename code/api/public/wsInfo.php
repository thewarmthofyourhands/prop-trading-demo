<?php

declare(strict_types=1);

use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
use Workerman\Worker;

error_reporting(E_ALL);

require_once __DIR__ . '/../vendor/autoload.php';

$ws = new Worker('websocket://0.0.0.0:2346');
$ws->count = 1;

$ws->onWorkerStart = static function (Worker $worker): void {
    $host = 'db';
    $port = 3306;
    $dbname = 'db';
    $username = 'user';
    $password = 'pass';

    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );

    Timer::add(1, static function () use ($worker, $pdo): void {
        try {
            $stmt = $pdo->query(
                'SELECT balance FROM trading_accounts WHERE id = 1'
            );

            $row = $stmt->fetch();

            if ($row === false) {
                return;
            }

            $message = json_encode([
                'type' => 'balance',
                'data' => $row['balance'],
            ], JSON_THROW_ON_ERROR);

            foreach ($worker->connections as $connection) {
                if ($connection->getStatus() === TcpConnection::STATUS_ESTABLISHED) {
                    $connection->send($message);
                }
            }
        } catch (\Throwable $e) {
            error_log('Balance broadcast error: ' . $e->getMessage());
        }
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