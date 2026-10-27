<?php

declare(strict_types=1);

namespace App\Exception\Application;

use App\Enum\Infrastructure\Rest\ApplicationErrorCodeEnum;

class NotFoundException extends ApplicationException
{
    public function __construct()
    {
        parent::__construct(ApplicationErrorCodeEnum::NOT_FOUND);
    }
}
