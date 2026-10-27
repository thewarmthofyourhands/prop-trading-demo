<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Contracts\Repository\UserRepositoryInterface;

final class UserService
{
    public function __construct(private readonly UserRepositoryInterface $userRepository) {}

    public function create(array $payload): array
    {
        return $this->userRepository->create($payload);
    }

    public function getById(int $id): null|array
    {
        return $this->userRepository->findById($id);
    }
}
