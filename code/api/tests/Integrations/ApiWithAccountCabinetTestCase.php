<?php

declare(strict_types=1);

namespace Tests\Integrations;

use Eva\Database\ConnectionStoreInterface;
use Tests\Integrations\Seed\AccountCabinetTestSeed;

class ApiWithAccountCabinetTestCase extends ApiWithAuthTestCase
{
    private function makeSeed(): void
    {
        $connectionStore = $this->application->getContainer()->get(ConnectionStoreInterface::class);
        assert($connectionStore instanceof ConnectionStoreInterface);
        AccountCabinetTestSeed::init($connectionStore);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeSeed();
    }
}
