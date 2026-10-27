<?php

declare(strict_types=1);

namespace App\Controller\Challenge;

use App\Infrastructure\Rest\ApiResponse;
use App\Service\Auth\AuthService;
use App\Service\Challenge\ChallengeAccountService;
use Eva\Http\Message\Request;
use Eva\Http\Message\ResponseInterface;

final class ChallengeAccountController
{
    public function __construct(
        private readonly ChallengeAccountService $challengeAccountService,
        private readonly AuthService $authService,
    ) {
    }

    public function index(Request $request): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        $items = $this->challengeAccountService->findAllByUserId((int) $user['id']);

        return (new ApiResponse($items, 20000, 'Ok'))->build();
    }

    public function show(Request $request, string $id): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        $item = $this->challengeAccountService->findOwnedById((int) $id, (int) $user['id']);

        return (new ApiResponse($item, 20000, 'Ok'))->build();
    }

    public function activate(Request $request, string $id): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        $this->challengeAccountService->activate((int) $id, (int) $user['id']);

        return (new ApiResponse(null, 20000, 'Ok'))->build();
    }
}
