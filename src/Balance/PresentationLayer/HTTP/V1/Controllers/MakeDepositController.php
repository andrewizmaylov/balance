<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Controllers;

use App\Http\Controllers\Controller;
use Psr\Log\LoggerInterface;
use DomainDriven\BaseDomainStructure\Responder\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Src\Balance\PresentationLayer\HTTP\V1\Requests\MakeDepositRequest;
use Src\Balance\ApplicationLayer\UseCases\MakeDepositUseCase;
use Src\Balance\PresentationLayer\HTTP\V1\Responders\MakeDepositResponder;
use Throwable;

final class MakeDepositController extends Controller
{
    public function __construct(
        public LoggerInterface $logger,
        public MakeDepositUseCase $process,
        public MakeDepositResponder $responder,
    ) {}

    public function __invoke(MakeDepositRequest $request): JsonResponse
    {
        try {
            $result = $this->process->execute();

            $response = new JsonResponse;
            $response->setData(
                $this->responder->composeEntity($result),
            );
        } catch (Throwable $exception) {
            $this->logger->critical(
                'An unexpected error occurred with MakeDepositController' . $exception->getMessage(),
                ['stacktrace' => $exception->getTraceAsString()],
            );

            $response = new JsonResponse();
            $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
            $response->setData([
                'errors' => [
                    [
                        'title' => 'internal_error',
                        'detail' => 'An unexpected error occurred.',
                    ],
                ],
            ]);

        }

        return $response;
    }
}
