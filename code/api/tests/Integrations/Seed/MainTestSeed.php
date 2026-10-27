<?php

declare(strict_types=1);

namespace Tests\Integrations\Seed;

use Eva\Database\ConnectionStoreInterface;

class MainTestSeed
{
    public static function init(ConnectionStoreInterface $connectionStore): void
    {
        try {
            $connection = $connectionStore->get();
            $connection->beginTransaction();

            $stmt = $connection->prepare(<<<'SQL'
                INSERT INTO challenges (
                    id,
                    name,
                    account_size,
                    price,
                    currency,
                    type,
                    rules,
                    created_at
                ) VALUES (
                    :id,
                    :name,
                    :account_size,
                    :price,
                    :currency,
                    :type,
                    :rules,
                    :created_at
                )
            SQL);

            $stmt->execute([
                'id' => 1,
                'name' => 'Stellar 1-Step',
                'account_size' => 60000000,
                'price' => 99,
                'currency' => 'USD',
                'type' => '1-step',
                'rules' => json_encode([
                    'profit_target' => 10,
                    'daily_loss_limit' => 3,
                    'maximum_loss_limit' => 6,
                    'minimum_trading_days' => 2,
                ], JSON_THROW_ON_ERROR),
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
