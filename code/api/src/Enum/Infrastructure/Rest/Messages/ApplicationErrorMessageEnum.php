<?php

declare(strict_types=1);

namespace App\Enum\Infrastructure\Rest\Messages;

use ValueError;

enum ApplicationErrorMessageEnum: string
{
    case SOMETHING_WENT_WRONG = 'Something went wrong';
    case VALIDATION_ERROR = 'Validation error';
    case AUTHENTICATION_ERROR = 'Your session has expired.';
    case AUTHORIZATION_ERROR = 'You don’t have permission to perform this action.';
    case NOT_FOUND = 'Resourse not found.';

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
