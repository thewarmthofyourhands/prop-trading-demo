<?php

declare(strict_types=1);

namespace App\Enum\Infrastructure\Rest\Messages;

use ValueError;

enum ApplicationSuccessMessageEnum: string
{
    case DEFAULT = 'Ok';
    case CREATED = 'Created';

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
