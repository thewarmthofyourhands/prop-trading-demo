<?php

declare(strict_types=1);

namespace App\Validation;

use App\Exception\Validation\ValidatorException;
use Opis\JsonSchema\Errors\ErrorFormatter;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator as OpValidator;

readonly class Validator
{
    private OpValidator $validator;

    public function __construct()
    {
        $this->validator = new OpValidator();
        $this->registerValidationSchemas();
    }

    private function registerValidationSchemas(): void
    {
        $this->validator->resolver()->registerPrefix(
            'validation://',
            'validations'
        );
    }

    /**
     * @throws ValidatorException
     */
    public function validate(null|string|array $data, string $id): void
    {
        if (is_array($data)) {
            $data = Helper::toJSON($data);
        }

        $result = $this->validator->validate($data, 'validation:/' . $id);

        if (false === $result->isValid()) {
            $errorList = (new ErrorFormatter())->format($result->error());
            throw new ValidatorException($errorList);
        }
    }
}
