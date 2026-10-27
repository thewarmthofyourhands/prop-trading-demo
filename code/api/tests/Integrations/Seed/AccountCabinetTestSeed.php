<?php

declare(strict_types=1);

namespace Tests\Integrations\Seed;

use Eva\Database\ConnectionStoreInterface;

class AccountCabinetTestSeed
{
    public static function init(ConnectionStoreInterface $connectionStore): void
    {
        try {
            $connection = $connectionStore->get();
            $connection->beginTransaction();

            $tradingAccountStmt = $connection->prepare(<<<'SQL'
                insert into trading_accounts (id, platform, balance, currency, leverage)
                values (:id, :platform, :balance, :currency, :leverage)
            SQL);
            $tradingAccountStmt->execute([
                'id' => 1,
                'platform' => 'native',
                'balance' => 62000000,
                'currency' => 'USD',
                'leverage' => 20,
            ]);
            $tradingAccountStmt->closeCursor();

            $challengeAccountStmt = $connection->prepare(<<<'SQL'
                insert into challenge_accounts (id, user_id, challenge_id, trading_account_id, status, created_at)
                values (:id, :user_id, :challenge_id, :trading_account_id, :status, :created_at)
            SQL);
            $challengeAccountStmt->execute([
                'id' => 1,
                'user_id' => 1,
                'challenge_id' => 1,
                'trading_account_id' => 1,
                'status' => 'active',
                'created_at' => '2026-10-24 14:01:00',
            ]);
            $challengeAccountStmt->closeCursor();

            $progressStmt = $connection->prepare(<<<'SQL'
                insert into challenge_account_progress (challenge_account_id, profit, loss, daily_loss, trading_days_completed)
                values (:challenge_account_id, :profit, :loss, :daily_loss, :trading_days_completed)
            SQL);
            $progressStmt->execute([
                'challenge_account_id' => 1,
                'profit' => 2000000,
                'loss' => 0,
                'daily_loss' => 0,
                'trading_days_completed' => 3,
            ]);
            $progressStmt->closeCursor();

            $connection->commit();
        } catch (\Throwable $e) {
            if (isset($connection) && $connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $e;
        }
    }
}
