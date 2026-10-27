<?php

declare(strict_types=1);

namespace App\Contracts\Repository;

interface OrderRepositoryInterface
{
    public function create(array $payload): array;

    public function findById(int $id): null|array;

    public function addPayment(int $id, array $payload): array;
}
