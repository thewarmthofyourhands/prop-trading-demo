<?php

declare(strict_types=1);

namespace App\Service\Auth;

use App\Exception\Application\AuthorizationException;
use App\Service\User\UserService;
use Eva\Http\Message\Request;

final class AuthService
{
    private const TOKEN = 'r4nd0mTok3nValue20';

    public function __construct(private readonly UserService $userService)
    {
    }

    public function authorize(Request $request): array
    {
        $token = (string) ($request->getHeaders()['authorization'] ?? '');
        $token = str_starts_with($token, 'Bearer ') ? substr($token, 7) : $token;

        if ($token !== self::TOKEN) {
            throw new AuthorizationException();
        }

        $user = $this->userService->getById(1);
        if (null === $user) {
            throw new AuthorizationException();
        }

        return $user;
    }
}
