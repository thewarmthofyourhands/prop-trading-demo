<?php

declare(strict_types=1);

namespace App\Exception\Validation;

use App\Enum\Infrastructure\Rest\ApplicationErrorCodeEnum;
use App\Exception\Application\ApplicationException;
use Exception;

class ValidatorException extends ApplicationException
{
    private readonly array $errors;

    public function __construct(array $errors)
    {
        $this->errors = $errors;
        parent::__construct(ApplicationErrorCodeEnum::VALIDATION_ERROR);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
