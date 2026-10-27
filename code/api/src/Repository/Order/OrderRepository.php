<?php

declare(strict_types=1);

namespace App\Repository\Order;

use App\Contracts\Repository\OrderRepositoryInterface;
use App\Exception\Application\NotFoundException;
use Eva\Database\ConnectionStoreInterface;
use PDO;

final readonly class OrderRepository implements OrderRepositoryInterface
{
    public function __construct(private ConnectionStoreInterface $connectionStore)
    {
    }

    public function create(array $payload): array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            insert into orders (user_id, challenge_id, amount, created_at)
            values (:user_id, :challenge_id, :amount, :created_at)
            SQL,
        );

        $stmt->execute([
            'user_id' => (int) ($payload['user_id'] ?? 0),
            'challenge_id' => (int) ($payload['challenge_id'] ?? 0),
            'amount' => $this->getChallengePrice((int) ($payload['challenge_id'] ?? 0)),
            'created_at' => $this->formatDateTime((string) ($payload['created_at'] ?? '2026-10-24 14:00:00')),
        ]);

        $id = (int) $connection->lastInsertId();
        $stmt->closeCursor();

        return ['id' => $id];
    }

    public function findById(int $id): null|array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            select
                o.id,
                o.user_id,
                o.challenge_id,
                o.amount,
                o.created_at,
                p.id as payment_id,
                p.transaction_id,
                p.amount as payment_amount,
                p.paid_at
            from orders o
            left join payments p on p.order_id = o.id
            where o.id = :id
            SQL,
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (false === $row) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'challenge_id' => (int) $row['challenge_id'],
            'amount' => (int) $row['amount'],
            'payment' => null === $row['payment_id'] ? null : [
                'id' => (int) $row['payment_id'],
                'transaction_id' => (string) $row['transaction_id'],
                'amount' => (int) $row['payment_amount'],
                'paid_at' => $this->formatDateTime((string) $row['paid_at'], true),
            ],
            'created_at' => $this->formatDateTime((string) $row['created_at'], true),
        ];
    }

    public function addPayment(int $id, array $payload): array
    {
        if (null === $this->findById($id)) {
            throw new NotFoundException();
        }

        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            insert into payments (order_id, transaction_id, amount, paid_at)
            values (:order_id, :transaction_id, :amount, :paid_at)
            SQL,
        );

        $stmt->execute([
            'order_id' => $id,
            'transaction_id' => (string) ($payload['transaction_id'] ?? 'safasf'),
            'amount' => (int) ($payload['amount'] ?? $this->getOrderAmount($id)),
            'paid_at' => $this->formatDateTime((string) ($payload['paid_at'] ?? '2026-10-24 14:05:00')),
        ]);

        $paymentId = (int) $connection->lastInsertId();
        $stmt->closeCursor();

        return ['id' => $paymentId];
    }

    private function getChallengePrice(int $challengeId): int
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare('select price from challenges where id = :id');
        $stmt->execute(['id' => $challengeId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (false === $row || !array_key_exists('price', $row)) {
            throw new NotFoundException();
        }

        return (int) $row['price'];
    }

    private function getOrderAmount(int $orderId): int
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare('select amount from orders where id = :id');
        $stmt->execute(['id' => $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (false === $row || !array_key_exists('amount', $row)) {
            throw new NotFoundException();
        }

        return (int) $row['amount'];
    }

    private function formatDateTime(string $value, bool $toApiFormat = false): string
    {
        if (false === $toApiFormat) {
            return $value;
        }

        return (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
