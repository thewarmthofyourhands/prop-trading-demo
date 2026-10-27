<?php

declare(strict_types=1);

use Eva\Http\HttpProtocolVersionEnum;
use Workerman\Connection\TcpConnection;
use Workerman\Protocols\Http\Request;
use Workerman\Worker;

error_reporting(E_ALL);
define("START_TIME", microtime(true));

require_once __DIR__ . '/../vendor/autoload.php';

$application = null;
$http_worker = new Worker("http://0.0.0.0:80");
$http_worker->count = 8;
$http_worker->onError = function($connection, $code, $msg) {
    var_dump($code, $msg);
};
$http_worker->onWorkerStart = static function(Worker $worker) use (&$application) {
    $application = new App\Application();
};

$http_worker->onMessage = static function(TcpConnection $connection, Request $wRequest) use (&$application) {
    try {
        $request = \App\Foundation\Http\WorkermanRequestCreator::createFromWorkermanRequest($wRequest);
        $response = $application->handle($request);
        $connection->send(\App\Foundation\Http\WorkermanResponseCreator::createWorkermanResponse($response));
    } catch (\Throwable $e) {
        print $e;
        $response = new \Eva\Http\Message\Response(
            500,
            [],
            'Something went wrong',
            HttpProtocolVersionEnum::from($wRequest->protocolVersion()),
        );
        $connection->send(\App\Foundation\Http\WorkermanResponseCreator::createWorkermanResponse($response));
    }
};

Worker::runAll();
