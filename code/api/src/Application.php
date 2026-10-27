<?php

declare(strict_types=1);

namespace App;

use Eva\Foundation\Http\HttpApplication;
use Eva\Http\Message\RequestInterface;
use Eva\Http\Message\ResponseInterface;
use Eva\HttpKernel\KernelInterface;

class Application extends HttpApplication
{
    public function terminate(RequestInterface $request, ResponseInterface $response): void
    {
        /** @var KernelInterface $kernel */
        $kernel = $this->getContainer()->get('kernel');
        $kernel->terminate($request, $response);
    }
}
