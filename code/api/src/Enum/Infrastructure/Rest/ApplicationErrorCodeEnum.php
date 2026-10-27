<?php

declare(strict_types=1);

namespace App\Enum\Infrastructure\Rest;

use ValueError;

enum ApplicationErrorCodeEnum: int
{
    case SOMETHING_WENT_WRONG = 50000;
    case VALIDATION_ERROR = 42200;
    case AUTHENTICATION_ERROR = 40100;
    case AUTHORIZATION_ERROR = 40300;
    case NOT_FOUND = 40400;

    public static function tryFromName(string $name): null|self
    {
        foreach (self::cases() as $status) {
            if ($name === $status->name){
                return $status;
            }
        }

        return null;
    }

    public static function fromName(string $name): self
    {
        $value = self::tryFromName($name);

        if (null === $value) {
            throw new ValueError("$name is not a valid backing value for enum " . self::class );
        }

        return $value;
    }
}
