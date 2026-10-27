<?php

declare(strict_types=1);

namespace App\Controller;

use App\Infrastructure\Rest\ApiResponse;
use App\Service\Auth\AuthService;
use App\Service\BalanceService;
use Eva\Http\Message\Request;
use Eva\Http\Message\ResponseInterface;

final class BalanceController
{
    public function __construct(
        private readonly BalanceService $balanceService,
        private readonly AuthService $authService,
    ) {}

    public function show(Request $request): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        $balance = $this->balanceService->getActiveBalanceForUser((int) $user['id']);

        return (new ApiResponse($balance, 20000, 'Ok'))->build();
    }
}
