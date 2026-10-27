<?php

declare(strict_types=1);

namespace App\Service;

use Eva\Database\ConnectionStoreInterface;
use Eva\Database\ConnectionInterface;

readonly class TransactionService
{
    private ConnectionInterface $connection;

    public function __construct(
        private ConnectionStoreInterface $connectionStore,
    ) {
        $this->connection = $connectionStore->get();
    }

    public function begin(): void
    {
        $this->connection->beginTransaction();
    }

    public function commit(): void
    {
        $this->connection->commit();
    }

    public function rollback(): void
    {
        $this->connection->rollback();
    }
}
