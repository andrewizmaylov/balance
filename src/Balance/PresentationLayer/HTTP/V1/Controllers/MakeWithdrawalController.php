<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Controllers;

use App\Http\Controllers\Controller;
use Psr\Log\LoggerInterface;
use DomainDriven\BaseDomainStructure\Responder\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Src\Balance\PresentationLayer\HTTP\V1\Requests\MakeWithdrawalRequest;
use Src\Balance\ApplicationLayer\UseCases\MakeWithdrawalUseCase;
use Src\Balance\PresentationLayer\HTTP\V1\Responders\BalanceTransactionResponder;
use Throwable;

final class MakeWithdrawalController extends Controller
{
    public function __construct(
        public LoggerInterface $logger,
        public MakeWithdrawalUseCase $process,
        public BalanceTransactionResponder $responder,
    ) {}

    public function __invoke(MakeWithdrawalRequest $request): JsonResponse
    {
        try {
            $result = $this->process->execute();

            $response = new JsonResponse;
            $response->setData(
                $this->responder->composeEntity($result),
            );
        } catch (Throwable $exception) {
            $this->logger->critical(
                'An unexpected error occurred with MakeWithdrawalController' . $exception->getMessage(),
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
