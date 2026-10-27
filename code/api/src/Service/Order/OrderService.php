<?php

declare(strict_types=1);

namespace App\Service\Order;

use App\Contracts\Repository\OrderRepositoryInterface;
use App\Exception\Application\AuthorizationException;
use App\Exception\Application\NotFoundException;
use App\Service\Challenge\ChallengeAccountService;

final class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ChallengeAccountService $challengeAccountService,
    ) {
    }

    public function create(array $payload): array
    {
        return $this->orderRepository->create($payload);
    }

    public function findById(int $id): null|array
    {
        return $this->orderRepository->findById($id);
    }

    public function getOwnedOrder(int $id, int $userId): array
    {
        $order = $this->findById($id);

        if (null === $order) {
            throw new NotFoundException();
        }

        if (($order['user_id'] ?? null) !== $userId) {
            throw new AuthorizationException();
        }

        return $order;
    }

    public function addPaymentForUser(int $id, int $userId, array $payload): array
    {
        $order = $this->getOwnedOrder($id, $userId);

        $payment = $this->orderRepository->addPayment($id, $payload);

        $this->challengeAccountService->createForUser((int) ($order['challenge_id'] ?? 0), $userId);

        return $payment;
    }
}
