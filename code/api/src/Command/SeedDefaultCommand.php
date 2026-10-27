<?php

declare(strict_types=1);

namespace App\Command;

use Eva\Console\ArgvInput;
use Eva\Database\ConnectionStoreInterface;
use Tests\Integrations\Seed\MainTestSeed;
use App\RequestService\CapitalRequestService;

readonly class SeedDefaultCommand
{
    private const BASE_URL = 'https://demo-api-capital.backend-capital.com';

    public function __construct(
        private ConnectionStoreInterface $connectionStore,
    ) {}

    public function execute(ArgvInput $argvInput): void
    {
      MainTestSeed::init($this->connectionStore);
      UserTestSeed::init($this->connectionStore);
    }
}