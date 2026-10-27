<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Rest\ApiResponse;
use Eva\Http\Message\Request;
use Eva\Http\Message\ResponseInterface;

final class WelcomeController
{
    public function index(Request $request): ResponseInterface
    {
        return (new ApiResponse(['msg' => 'Hi']))->build();
    }
}
