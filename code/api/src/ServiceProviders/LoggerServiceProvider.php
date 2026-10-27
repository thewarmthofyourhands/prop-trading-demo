<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use Psr\Log\LoggerInterface;
use Eva\DependencyInjection\ContainerInterface;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LoggerServiceProvider
{
    public function __construct(LoggerInterface $logger, ContainerInterface $container)
    {
        $projectDir = $container->getParameter('kernel.project_dir');
        assert($logger instanceof Logger);
        $logger->pushHandler(new StreamHandler($projectDir . '/var/log/app.log', Level::Warning));
    }
}
