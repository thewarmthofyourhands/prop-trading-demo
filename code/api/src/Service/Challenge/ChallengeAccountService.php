<?php

declare(strict_types=1);

namespace App\Service\Challenge;

use App\Contracts\Repository\ChallengeAccountRepositoryInterface;
use App\Exception\Application\NotFoundException;

final class ChallengeAccountService
{
    public function __construct(private readonly ChallengeAccountRepositoryInterface $challengeAccountRepository)
    {
    }

    public function findActiveByUserId(int $userId): array
    {
        $items = $this->findAllByUserId($userId);

        foreach ($items as $item) {
            if (($item['status'] ?? null) === 'active') {
                return $item;
            }
        }

        throw new NotFoundException();
    }

    public function findAllByUserId(int $userId): array
    {
        return $this->challengeAccountRepository->findAllByUserId($userId);
    }

    public function findOwnedById(int $id, int $userId): array
    {
        $item = $this->challengeAccountRepository->findOwnedById($id, $userId);

        if (null === $item) {
            throw new NotFoundException();
        }

        return $item;
    }

    public function activate(int $id, int $userId): void
    {
        $this->challengeAccountRepository->activate($id, $userId);
    }

    public function createForUser(int $challengeId, int $userId): void
    {
        $this->challengeAccountRepository->createForUser($challengeId, $userId);
    }

    public function addProfitToActiveAccount(int $userId, int $profit): void
    {
        $account = $this->findActiveByUserId($userId);
        $this->challengeAccountRepository->addProfit((int) $account['id'], (int) $account['trading_account']['id'], $profit);
    }
}
