<?php

declare(strict_types=1);

namespace App\Repository\Challenge;

use App\Contracts\Repository\ChallengeRepositoryInterface;
use Eva\Database\ConnectionStoreInterface;
use PDO;

final readonly class ChallengeRepository implements ChallengeRepositoryInterface
{
    public function __construct(private ConnectionStoreInterface $connectionStore)
    {
    }

    public function findAll(): array
    {
        $connection = $this->connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            select id, name, account_size, price, currency, type, rules, created_at
            from challenges
            order by id asc
            SQL,
        );
        $stmt->execute();

        $items = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rules = json_decode((string) $row['rules'], true, 512, JSON_THROW_ON_ERROR);
            $items[] = [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'account_size' => (int) $row['account_size'],
                'price' => (int) $row['price'],
                'currency' => $row['currency'],
                'type' => $row['type'],
                'rules' => $rules,
            ];
        }

        $stmt->closeCursor();

        return $items;
    }
}
