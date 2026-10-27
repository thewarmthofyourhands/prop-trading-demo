<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Infrastructure\Rest\ApiResponse;
use App\Service\User\UserService;
use Eva\Http\Message\Request;
use Eva\Http\Message\ResponseInterface;
use Eva\Http\Parser\JsonRequestParser;

final class UserController
{
    public function __construct(private readonly UserService $userService) {}

    public function store(Request $request): ResponseInterface
    {
        $payload = JsonRequestParser::parseBody($request);
        $created = $this->userService->create($payload);

        return (new ApiResponse(['id' => (int) ($created['id'] ?? 0)], 20100, 'Ok'))->build(201);
    }
}
