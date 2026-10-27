<?php

declare(strict_types=1);

namespace App\Service;

use App\RequestService\CapitalRequestService;
use App\Service\BalanceService;

readonly class CapitalService
{
    public function __construct(
        private CapitalRequestService $capitalRequestService,
        private BalanceService $balanceService,
    ) {}

    public function getMarkets(): array
    {
        return $this->capitalRequestService->getMarkets();
    }

    public function getMarketPrices(string $epic): array
    {
        return $this->capitalRequestService->getMarketPrices($epic);
    }

    public function getPositions(): array
    {
        return $this->capitalRequestService->getPositions();
    }

    public function getPosition(string $dealId): array
    {
        return $this->capitalRequestService->getPosition($dealId);
    }

    public function createPosition(string $epic, string $direction, float $size): array
    {
        return $this->capitalRequestService->createPosition($epic, $direction, $size);
    }

    public function closePosition(string $dealId, int $userId): array
    {
        $closeResponse = $this->capitalRequestService->closePosition($dealId);
        sleep(2);
        $confirmResp = $this->capitalRequestService->getConfirm($closeResponse['dealReference']);
        $profit = (int) round($confirmResp['profit'] * 10000);
        $this->balanceService->addProfit($userId, $profit);
        $closeResponse['confirmResp'] = $confirmResp;
        $closeResponse['confirmProfit'] = $profit;

        return $closeResponse;
    }

    public function getHistory(): array
    {
        return $this->capitalRequestService->getHistoryForLastMonth();
    }
}
