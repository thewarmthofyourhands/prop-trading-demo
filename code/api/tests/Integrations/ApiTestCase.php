<?php

declare(strict_types=1);

namespace Tests\Integrations;

use App\Infrastructure\Redis\RedisManager;
use App\TestApplication;
use App\Application;
use Eva\Database\ConnectionStoreInterface;
use PHPUnit\Framework\TestCase;
use Tests\Integrations\Seed\MainTestSeed;

class ApiTestCase extends TestCase
{
    protected Application $application;

    private function clearRedis(): void
    {
        $redisManager = $this->application->getContainer()->get(RedisManager::class);
        assert($redisManager instanceof RedisManager);
        $allKeys = $redisManager->keys();

        foreach ($allKeys as $key) {
            $redisManager->del($key);
        }
    }

    private function clearDb(): void
    {
        $connectionStore = $this->application->getContainer()->get(ConnectionStoreInterface::class);
        assert($connectionStore instanceof ConnectionStoreInterface);
        $connection = $connectionStore->get();
        $stmt = $connection->prepare(<<<SQL
            SELECT table_name
            FROM information_schema.tables
            WHERE table_schema = 'db'
            SQL,
        );
        $stmt->execute();
        $tableTruncList = [];

        while ($el = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if ('migrations' === $el['table_name']) {
                continue;
            }

            $tableTruncList[] = $el['table_name'];
        }

        $stmt->closeCursor();

        foreach ($tableTruncList as $tableTrunc) {
            $stmt = $connection->prepare(<<<SQL
                SET FOREIGN_KEY_CHECKS = 0;
                TRUNCATE TABLE $tableTrunc;
                SET FOREIGN_KEY_CHECKS = 1;
                SQL,
            );
            $stmt->execute();
            $stmt->closeCursor();
        }
    }

    private function makeSeed(): void
    {
        $connectionStore = $this->application->getContainer()->get(ConnectionStoreInterface::class);
        assert($connectionStore instanceof ConnectionStoreInterface);
        MainTestSeed::init($connectionStore);
    }

    protected function setUp(): void
    {
        $this->application = new TestApplication();
        $this->clearRedis();
        $this->clearDb();
        $this->makeSeed();

        parent::setUp();
    }
}
