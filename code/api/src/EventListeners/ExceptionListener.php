<?php

declare(strict_types=1);

namespace App\EventListeners;

use App\Enum\Infrastructure\Rest\ApplicationErrorCodeEnum;
use App\Enum\Infrastructure\Rest\Messages\ApplicationErrorMessageEnum;
use App\Exception\Application\AuthenticationException;
use App\Exception\Application\AuthorizationException;
use App\Exception\Application\NotFoundException;
use App\Exception\Validation\ValidatorException;
use App\Exception\Application\ApplicationException;
use App\Infrastructure\Rest\ApiResponse;
use Eva\Http\Message\Response;
use Eva\HttpKernel\Events\ExceptionEvent;
use Eva\HttpKernel\Exceptions\HttpException;
use Psr\Log\LoggerInterface;

class ExceptionListener
{
    public function __construct(
        private readonly string $appDev,
        private LoggerInterface $logger,
    ) {}

    public function __invoke(ExceptionEvent $exceptionEvent): ExceptionEvent
    {
        $exceptionEvent->setResponse(new Response(500));
        $throwable = $exceptionEvent->getThrowable();
        $debug = null;

        if ('dev' === $this->appDev) {
            $debug = [
                'rawMessage' => $throwable->getMessage(),
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
                'trace' => explode(PHP_EOL, $throwable->getTraceAsString()),
            ];
        }

        if ($throwable instanceof ApplicationException) {
            $data = null;
            $httpCode = 400;
            $applicationCode = $throwable->getCode();
            $applicationMessage = $throwable->getMessage();

            if ($throwable instanceof ValidatorException) {
                $data = $throwable->getErrors();
                $httpCode = 422;
            }

            if ($throwable instanceof AuthenticationException) {
                $httpCode = 401;
            }

            if ($throwable instanceof AuthorizationException) {
                $httpCode = 403;
            }

            if ($throwable instanceof NotFoundException) {
                $httpCode = 404;
            }
        } else if ($throwable instanceof HttpException) {
            $data = null;
            $httpCode = $throwable->getResponseStatusCode();
            $applicationCode = $throwable->getCode();
            $applicationMessage = $throwable->getMessage();
        } else {
            $data = null;
            $httpCode = 500;
            $applicationCode = ApplicationErrorCodeEnum::SOMETHING_WENT_WRONG->value;
            $applicationMessage = ApplicationErrorMessageEnum::SOMETHING_WENT_WRONG->value;
            $this->logger->error($throwable->getMessage(), [
                'file' => $throwable->getFile(),
                'line' => $throwable->getLine(),
            ]);
        }

        $apiResponse = new ApiResponse($data, $applicationCode, $applicationMessage);
        $response = $apiResponse->setDebug($debug)->build($httpCode);
        $exceptionEvent->setResponse($response);

        return $exceptionEvent;
    }
}
