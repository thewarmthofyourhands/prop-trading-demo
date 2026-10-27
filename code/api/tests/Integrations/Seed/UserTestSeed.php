<?php

declare(strict_types=1);

namespace Tests\Integrations\Seed;

use Eva\Database\ConnectionStoreInterface;

class UserTestSeed
{
    public static function init(ConnectionStoreInterface $connectionStore): void
    {
        try {
            $connection = $connectionStore->get();
            $connection->beginTransaction();

            $stmt = $connection->prepare(<<<'SQL'
                insert into users (id, email, created_at)
                values (:id, :email, :created_at)
            SQL);

            $stmt->execute([
                'id' => 1,
                'email' => 'test@test.co',
                'created_at' => '2026-10-24 14:00:00',
            ]);

            $stmt->closeCursor();
            $connection->commit();
        } catch (\Throwable $e) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $e;
        }
    }
}
