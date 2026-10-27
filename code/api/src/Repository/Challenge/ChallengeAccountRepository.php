<?php

declare(strict_types=1);

namespace App\Repository\Challenge;

use App\Contracts\Repository\ChallengeAccountRepositoryInterface;
use App\Exception\Application\NotFoundException;
use Eva\Database\ConnectionStoreInterface;
use PDO;

final readonly class ChallengeAccountRepository implements ChallengeAccountRepositoryInterface
{
    public function __construct(private ConnectionStoreInterface $connectionStore)
    {
    }

    public function findAllByUserId(int $userId): array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            select
                ca.id,
                ca.status,
                ca.created_at,
                c.id as challenge_id,
                c.name as challenge_name,
                ta.id as trading_account_id,
                ta.platform,
                ta.balance,
                ta.currency,
                ta.leverage
            from challenge_accounts ca
            join challenges c on c.id = ca.challenge_id
            join trading_accounts ta on ta.id = ca.trading_account_id
            where ca.user_id = :user_id
            order by ca.id asc
            SQL,
        );
        $stmt->execute(['user_id' => $userId]);

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $items[] = [
                'id' => (int) $row['id'],
                'status' => $row['status'],
                'created_at' => $this->formatDateTime((string) $row['created_at']),
                'challenge' => [
                    'id' => (int) $row['challenge_id'],
                    'name' => $row['challenge_name'],
                ],
                'trading_account' => [
                    'id' => (int) $row['trading_account_id'],
                    'platform' => $row['platform'],
                    'balance' => (int) $row['balance'],
                    'currency' => $row['currency'],
                    'leverage' => (int) $row['leverage'],
                ],
            ];
        }

        $stmt->closeCursor();

        return $items;
    }

    public function findOwnedById(int $id, int $userId): null|array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            select
                ca.id,
                ca.status,
                ca.created_at,
                c.id as challenge_id,
                c.name as challenge_name,
                c.account_size,
                c.price,
                c.currency as challenge_currency,
                c.type,
                c.rules,
                ta.id as trading_account_id,
                ta.platform,
                ta.balance,
                ta.currency,
                ta.leverage,
                cap.profit,
                cap.loss,
                cap.daily_loss,
                cap.trading_days_completed
            from challenge_accounts ca
            join challenges c on c.id = ca.challenge_id
            join trading_accounts ta on ta.id = ca.trading_account_id
            left join challenge_account_progress cap on cap.challenge_account_id = ca.id
            where ca.id = :id and ca.user_id = :user_id
            SQL,
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (false === $row) {
            return null;
        }

        $rules = json_decode((string) $row['rules'], true, 512, JSON_THROW_ON_ERROR);

        return [
            'id' => (int) $row['id'],
            'status' => $row['status'],
            'created_at' => $this->formatDateTime((string) $row['created_at']),
            'challenge' => [
                'id' => (int) $row['challenge_id'],
                'name' => $row['challenge_name'],
                'account_size' => (int) $row['account_size'],
                'price' => (int) $row['price'],
                'currency' => $row['challenge_currency'],
                'type' => $row['type'],
                'rules' => $rules,
            ],
            'trading_account' => [
                'id' => (int) $row['trading_account_id'],
                'platform' => $row['platform'],
                'balance' => (int) $row['balance'],
                'currency' => $row['currency'],
                'leverage' => (int) $row['leverage'],
            ],
            'progress' => [
                'profit' => (int) ($row['profit'] ?? 0),
                'loss' => (int) ($row['loss'] ?? 0),
                'daily_loss' => (int) ($row['daily_loss'] ?? 0),
                'trading_days_completed' => (int) ($row['trading_days_completed'] ?? 0),
            ],
            'violations' => [],
        ];
    }

    public function activate(int $id, int $userId): void
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            update challenge_accounts
            set status = 'active'
            where id = :id and user_id = :user_id
            SQL,
        );
        $stmt->execute(['id' => $id, 'user_id' => $userId]);
        $stmt->closeCursor();

        $checkStmt = $connection->prepare('select 1 from challenge_accounts where id = :id and user_id = :user_id');
        $checkStmt->execute(['id' => $id, 'user_id' => $userId]);
        $found = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $checkStmt->closeCursor();

        if (false === $found) {
            throw new NotFoundException();
        }
    }

    public function createForUser(int $challengeId, int $userId): void
    {
        $connection = $this->connectionStore->get();
        $connection->beginTransaction();

        try {
            $tradingAccountId = $this->createTradingAccount($challengeId);
            $stmt = $connection->prepare(<<<SQL
                insert into challenge_accounts (user_id, challenge_id, trading_account_id, status, created_at)
                values (:user_id, :challenge_id, :trading_account_id, :status, :created_at)
                SQL,
            );
            $stmt->execute([
                'user_id' => $userId,
                'challenge_id' => $challengeId,
                'trading_account_id' => $tradingAccountId,
                'status' => 'inactive',
                'created_at' => '2026-10-24 14:01:00',
            ]);
            $challengeAccountId = (int) $connection->lastInsertId();
            $stmt->closeCursor();

            $progressStmt = $connection->prepare(<<<SQL
                insert into challenge_account_progress (challenge_account_id, profit, loss, daily_loss, trading_days_completed)
                values (:challenge_account_id, 0, 0, 0, 0)
                SQL,
            );
            $progressStmt->execute(['challenge_account_id' => $challengeAccountId]);
            $progressStmt->closeCursor();

            $connection->commit();
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $e;
        }
    }

    public function addProfit(int $challengeAccountId, int $tradingAccountId, int $profit): void
    {
        $connection = $this->connectionStore->get();
        $connection->beginTransaction();

        try {
            $balanceStmt = $connection->prepare('update trading_accounts set balance = balance + :profit where id = :id');
            $balanceStmt->execute(['profit' => $profit, 'id' => $tradingAccountId]);
            $balanceStmt->closeCursor();

            $progressStmt = $connection->prepare('update challenge_account_progress set profit = profit + :profit where challenge_account_id = :id');
            $progressStmt->execute(['profit' => $profit, 'id' => $challengeAccountId]);
            $progressStmt->closeCursor();

            $connection->commit();
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $e;
        }
    }

    private function createTradingAccount(int $challengeId): int
    {
        $connection = $this->connectionStore->get();
        $challengeStmt = $connection->prepare('select account_size, currency from challenges where id = :id');
        $challengeStmt->execute(['id' => $challengeId]);
        $challengeRow = $challengeStmt->fetch(PDO::FETCH_ASSOC);
        $challengeStmt->closeCursor();

        if (false === $challengeRow) {
            throw new NotFoundException();
        }

        $stmt = $connection->prepare(<<<SQL
            insert into trading_accounts (platform, balance, currency, leverage)
            values (:platform, :balance, :currency, :leverage)
            SQL,
        );
        $stmt->execute([
            'platform' => 'mt5',
            'balance' => (int) $challengeRow['account_size'],
            'currency' => $challengeRow['currency'],
            'leverage' => 100,
        ]);
        $id = (int) $connection->lastInsertId();
        $stmt->closeCursor();

        return $id;
    }

    private function formatDateTime(string $value): string
    {
        return (new \DateTimeImmutable($value))->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
