<?php

declare(strict_types=1);

namespace App\Service\Challenge;

use App\Contracts\Repository\ChallengeRepositoryInterface;

final class ChallengeService
{
    public function __construct(private readonly ChallengeRepositoryInterface $challengeRepository)
    {
    }

    public function findAll(): array
    {
        return $this->challengeRepository->findAll();
    }
}
