<?php

declare(strict_types=1);

namespace App\Contracts\Repository;

interface ChallengeRepositoryInterface
{
    public function findAll(): array;
}
