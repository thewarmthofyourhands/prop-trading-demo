<?php

declare(strict_types=1);

namespace App\Controller\Capital;

use App\Infrastructure\Rest\ApiResponse;
use App\Service\Auth\AuthService;
use App\Service\CapitalService;
use Eva\Http\Message\Request;
use Eva\Http\Message\ResponseInterface;
use Eva\Http\Parser\JsonRequestParser;

final class CapitalController
{
    public function __construct(
        private readonly CapitalService $capitalService,
        private readonly AuthService $authService,
    ) {}

    public function markets(Request $request): ResponseInterface
    {
        $markets = $this->capitalService->getMarkets();

        return (new ApiResponse($markets, 20000, 'Ok'))->build();
    }

    public function marketPrices(Request $request, string $id): ResponseInterface
    {
        $prices = $this->capitalService->getMarketPrices($id);

        return (new ApiResponse($prices, 20000, 'Ok'))->build();
    }

    public function positions(Request $request): ResponseInterface
    {
        $positions = $this->capitalService->getPositions();

        return (new ApiResponse($positions, 20000, 'Ok'))->build();
    }

    public function position(Request $request, string $id): ResponseInterface
    {
        $position = $this->capitalService->getPosition($id);

        return (new ApiResponse($position, 20000, 'Ok'))->build();
    }

    public function createPosition(Request $request): ResponseInterface
    {
        $payload = JsonRequestParser::parseBody($request);
        $created = $this->capitalService->createPosition(
            (string) ($payload['epic'] ?? ''),
            (string) ($payload['direction'] ?? ''),
            (float) ($payload['size'] ?? 0),
        );

        return (new ApiResponse($created, 20100, 'Ok'))->build(201);
    }

    public function closePosition(Request $request, string $id): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        $result = $this->capitalService->closePosition($id, (int) $user['id']);

        return (new ApiResponse($result, 20000, 'Ok'))->build();
    }

    public function history(Request $request): ResponseInterface
    {
        $history = $this->capitalService->getHistory();

        return (new ApiResponse($history, 20000, 'Ok'))->build();
    }
}
