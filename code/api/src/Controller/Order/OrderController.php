<?php

declare(strict_types=1);

namespace App\Controller\Order;

use App\Infrastructure\Rest\ApiResponse;
use App\Service\Auth\AuthService;
use App\Service\Order\OrderService;
use Eva\Http\Message\Request;
use Eva\Http\Message\ResponseInterface;
use Eva\Http\Parser\JsonRequestParser;

final class OrderController
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly AuthService $authService,
    ) {
    }

    public function store(Request $request): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        $payload = JsonRequestParser::parseBody($request);
        $created = $this->orderService->create([
            'user_id' => $user['id'],
            'challenge_id' => $payload['challenge_id'],
        ]);

        return (new ApiResponse(['id' => $created['id']], 20100, 'Ok'))->build(201);
    }

    public function show(Request $request, string $id): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        $order = $this->orderService->getOwnedOrder((int) $id, (int) $user['id']);

        return (new ApiResponse($order, 20000, 'Ok'))->build();
    }

    public function payment(Request $request, string $id): ResponseInterface
    {
        $user = $this->authService->authorize($request);
        // $payload = JsonRequestParser::parseBody($request);
        $paid = $this->orderService->addPaymentForUser((int) $id, (int) $user['id'], []);

        return (new ApiResponse(['id' => $paid['id']], 20100, 'Ok'))->build(201);
    }
}
