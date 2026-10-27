<?php

declare(strict_types=1);

namespace Tests\Integrations;

use Eva\Database\ConnectionStoreInterface;
use Tests\Integrations\Seed\UserTestSeed;

class ApiWithAuthTestCase extends ApiTestCase
{
    private function makeSeed(): void
    {
        $connectionStore = $this->application->getContainer()->get(ConnectionStoreInterface::class);
        assert($connectionStore instanceof ConnectionStoreInterface);
        UserTestSeed::init($connectionStore);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeSeed();
    }

    protected function getAuthHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Authorization' => 'r4nd0mTok3nValue20',
        ];
    }
}
