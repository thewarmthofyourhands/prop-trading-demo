<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use Eva\Database\ConnectionInterface;
use Eva\Database\LevelTransactionEnum;
use Eva\Database\PDO\Statement;
use PDO;

class StatefullConnection implements ConnectionInterface
{
    protected PDO $pdo;

    protected string $dsn;

    protected int $lastConnectionTime;
    protected bool $isConnecting = false;

    public function __construct(
        string $host,
        string $port,
        protected string $databaseName,
        protected string $username,
        protected string $password,
    ) {
        $this->dsn = "mysql:host=$host;port=$port;dbname=$databaseName";
        $this->createConnection();
    }

    protected function createConnection(): void
    {
        if (false === $this->isConnecting) {
            $this->lastConnectionTime = time();
            $this->isConnecting = true;
            $this->pdo = new PDO($this->dsn, $this->username, $this->password);
            $this->isConnecting = false;

            return;
        }

        sleep(1);
    }

    protected function checkConnection(): void
    {
        $lifetime = 3600;

        if ((time() - $this->lastConnectionTime) > $lifetime) {
            $this->createConnection();
        }
    }

    public function getDatabaseName(): string
    {
        return $this->databaseName;
    }

    public function setLevelTransaction(LevelTransactionEnum $levelTransaction): void
    {
        $this->checkConnection();
        $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL ' . $levelTransaction->value . ';');
    }

    public function beginTransaction(): void
    {
        $this->checkConnection();
        $this->pdo->beginTransaction();
    }

    public function rollback(): void
    {
        $this->checkConnection();
        $this->pdo->rollBack();
    }

    public function commit(): void
    {
        $this->checkConnection();
        $this->pdo->commit();
    }

    public function inTransaction(): bool
    {
        $this->checkConnection();
        return $this->pdo->inTransaction();
    }

    public function prepare(string $sql, null|array $parameters = null, array $options = []): Statement
    {
        $this->checkConnection();

        if (null !== $parameters) {
            $listParameters = [];

            foreach ($parameters as $parameterName => $parameterValue) {
                if (true === is_array($parameterValue)) {
                    $newSqlParameterNameList = [];

                    foreach ($parameterValue as $key => $item) {
                        $newParameterName = $parameterName . '_' . $key;
                        $newSqlParameterNameList[] = ':'.$newParameterName;
                        $listParameters[$parameterName . '_' . $key] = $item;
                    }

                    $sql = str_replace(':' . $parameterName, implode(', ', $newSqlParameterNameList), $sql);
                    unset($parameters[$parameterName]);
                }
            }

            $parameters = array_merge($parameters, $listParameters);
            $stmt = new Statement($this->pdo->prepare($sql, $options));

            foreach ($parameters as $parameterName => $parameterValue) {
                $stmt->bindParam(':' . $parameterName, $parameterValue);
            }
        } else {
            $stmt = new Statement($this->pdo->prepare($sql, $options));
        }

        return $stmt;
    }

    public function lastInsertId(): string
    {
        $this->checkConnection();
        return $this->pdo->lastInsertId();
    }

    public function execute(string $sql): void
    {
        $this->checkConnection();
        $this->pdo->exec($sql);
    }

    public function getNativeConnection(): PDO
    {
        $this->checkConnection();
        return $this->pdo;
    }
}
