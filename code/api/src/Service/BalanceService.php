<?php

declare(strict_types=1);

namespace App\Service;

use App\Service\Challenge\ChallengeAccountService;

final readonly class BalanceService
{
    public function __construct(private ChallengeAccountService $challengeAccountService) {}

    public function getActiveBalanceForUser(int $userId): array
    {
        $account = $this->challengeAccountService->findActiveByUserId($userId);

        return [
            'challenge_account_id' => $account['id'],
            'trading_account_id' => $account['trading_account']['id'],
            'currency' => $account['trading_account']['currency'],
            'balance' => $account['trading_account']['balance'],
            'equity' => $account['trading_account']['equity'],
        ];
    }

    public function addProfit(int $userId, int $profit): void
    {
        $this->challengeAccountService->addProfitToActiveAccount($userId, $profit);
    }
}
