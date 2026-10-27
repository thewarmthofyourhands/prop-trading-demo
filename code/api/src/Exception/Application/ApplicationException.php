<?php

declare(strict_types=1);

namespace App\Exception\Application;

use App\Enum\Infrastructure\Rest\ApplicationErrorCodeEnum;
use App\Enum\Infrastructure\Rest\Messages\ApplicationErrorMessageEnum;
use Throwable;
use Exception;

class ApplicationException extends Exception
{
    public function __construct(ApplicationErrorCodeEnum $code, ?Throwable $previous = null)
    {
        parent::__construct($this->getMessageByCode($code)->value, $code->value, $previous);
    }

    private function getMessageByCode(ApplicationErrorCodeEnum $code): ApplicationErrorMessageEnum
    {
        return ApplicationErrorMessageEnum::fromName($code->name);
    }
}
