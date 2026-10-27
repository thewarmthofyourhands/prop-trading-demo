<?php

declare(strict_types=1);

namespace App\Controller\Challenge;

use App\Infrastructure\Rest\ApiResponse;
use App\Service\Challenge\ChallengeService;
use Eva\Http\Message\Request;
use Eva\Http\Message\ResponseInterface;

final class ChallengeController
{
    public function __construct(private readonly ChallengeService $challengeService) {}

    public function index(Request $request): ResponseInterface
    {
        $items = $this->challengeService->findAll();

        return (new ApiResponse($items, 20000, 'Ok'))->build();
    }
}
