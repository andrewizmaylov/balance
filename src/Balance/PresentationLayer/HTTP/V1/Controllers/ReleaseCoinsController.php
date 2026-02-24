<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Controllers;

use App\Http\Controllers\Controller;
use DomainDriven\BaseDomainStructure\Responder\JsonResponse;
use Psr\Log\LoggerInterface;
use Src\Balance\ApplicationLayer\UseCases\ReleaseCoinsUseCase;
use Src\Balance\DomainLayer\Exceptions\UnconfirmedTransactionException;
use Src\Balance\PresentationLayer\HTTP\V1\Requests\ReleaseCoinsRequest;
use Src\Balance\PresentationLayer\HTTP\V1\Responders\BalanceTransactionResponder;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ReleaseCoinsController extends Controller
{
    public function __construct(
        public LoggerInterface $logger,
        public ReleaseCoinsUseCase $process,
        public BalanceTransactionResponder $responder,
    ) {}

    public function __invoke(ReleaseCoinsRequest $request): JsonResponse
    {
        try {
            $result = $this->process->execute(
                $request->validated('transaction_id'),
            );

            $response = new JsonResponse;
            $response->setData(
                $this->responder->composeEntity($result),
            );
        } catch (UnconfirmedTransactionException $exception) {
            $response = new JsonResponse();
            $response->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
            $response->setData([
                'errors' => [
                    [
                        'title' => 'unconfirmed_transaction',
                        'detail' => $exception->getMessage(),
                    ],
                ],
            ]);
        } catch (Throwable $exception) {
            $this->logger->critical(
                'An unexpected error occurred with ReleaseCoinsController' . $exception->getMessage(),
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
