<?php

declare(strict_types=1);

namespace App\Repository\User;

use App\Contracts\Repository\UserRepositoryInterface;
use Eva\Database\ConnectionStoreInterface;
use PDO;

final readonly class UserRepository implements UserRepositoryInterface
{
    public function __construct(private ConnectionStoreInterface $connectionStore)
    {
    }

    public function create(array $payload): array
    {
        $connection = $this->connectionStore->get();
        $email = (string) ($payload['email'] ?? '');
        $createdAt = (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');

        $stmt = $connection->prepare(<<<SQL
            insert into users (email, created_at)
            values (:email, :created_at)
            SQL,
            [
                'email' => $email,
                'created_at' => $createdAt,
            ],
        );
        $stmt->execute();

        $id = (int) $connection->lastInsertId();
        $fetchStmt = $connection->prepare(<<<SQL
            select id, email, created_at
            from users
            where id = :id
            SQL,
            ['id' => $id],
        );
        $fetchStmt->execute();
        $row = $fetchStmt->fetch(PDO::FETCH_ASSOC);
        $fetchStmt->closeCursor();

        return false === $row ? ['id' => $id] : [
            'id' => (int) $row['id'],
            'email' => $row['email'],
            'created_at' => (new \DateTimeImmutable($row['created_at']))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z'),
        ];
    }

    public function findById(int $id): null|array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            select id, email, created_at
            from users
            where id = :id
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
            'email' => $row['email'],
            'created_at' => (new \DateTimeImmutable($row['created_at']))
                ->setTimezone(new \DateTimeZone('UTC'))
                ->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
