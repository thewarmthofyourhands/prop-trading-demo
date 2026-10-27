<?php

declare(strict_types=1);

namespace App\Contracts\Repository;

interface UserRepositoryInterface
{
    public function create(array $payload): array;

    public function findById(int $id): null|array;
}
