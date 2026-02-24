<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Controllers;

use App\Http\Controllers\Controller;
use Psr\Log\LoggerInterface;
use DomainDriven\BaseDomainStructure\Responder\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Src\Balance\PresentationLayer\HTTP\V1\Requests\PutOrderRequest;
use Src\Balance\ApplicationLayer\UseCases\PutOrderUseCase;
use Src\Balance\PresentationLayer\HTTP\V1\Responders\BalanceTransactionResponder;
use Throwable;

final class PutOrderController extends Controller
{
    public function __construct(
        public LoggerInterface $logger,
        public PutOrderUseCase $process,
        public BalanceTransactionResponder $responder,
    ) {}

    public function __invoke(PutOrderRequest $request): JsonResponse
    {
        try {
            $result = $this->process->execute($request->all());

            $response = new JsonResponse;
            $response->setData(
                $this->responder->composeEntity($result),
            );
        } catch (Throwable $exception) {
            $this->logger->critical(
                'An unexpected error occurred with PutOrderController' . $exception->getMessage(),
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
