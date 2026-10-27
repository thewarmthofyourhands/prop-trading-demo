<?php

declare(strict_types=1);

namespace App\Contracts\Repository;

interface ChallengeAccountRepositoryInterface
{
    public function findAllByUserId(int $userId): array;

    public function findOwnedById(int $id, int $userId): null|array;

    public function activate(int $id, int $userId): void;

    public function createForUser(int $challengeId, int $userId): void;

    public function addProfit(int $challengeAccountId, int $tradingAccountId, int $profit): void;
}
