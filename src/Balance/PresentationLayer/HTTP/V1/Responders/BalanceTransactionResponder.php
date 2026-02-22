<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Responders;

use DomainDriven\BaseDomainStructure\Responder\Contracts\ResponderInterface;
use DomainDriven\BaseDomainStructure\Responder\PaginatedResult;
use Exception;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;


class BalanceTransactionResponder implements ResponderInterface
{
    /**
     * @param PaginatedResult $paginatedResults
     * @return PaginatedResult
     * @throws Exception
     */
    public function composePaginatedResults(PaginatedResult $paginatedResults): PaginatedResult
    {
        $processedItems = array_map(fn($record) => $this->composeEntity($record), $paginatedResults->items);

        $paginatedResults->withProcessedItems($processedItems);

        return $paginatedResults;
    }

    /**
     * @throws Exception
     */
    public function composeEntity(object $entity): array
    {
        if (!$entity instanceof BalanceTransaction) {
            throw new Exception('Received unsupported entity ' . BalanceTransaction::class);
        }

        return [
            'id' => $entity->id,
            'type' => 'BalanceTransaction',
            'attributes' => [
                'id' => $entity->id,
                'account_id' => $entity->accountId,
                'coin' => $entity->coin->value,
                'amount' => $entity->amount,
                'chain_name' => $entity->chainName,
                'chain_type' => $entity->chainType?->value,
                'address' => $entity->address,
                'transaction_id' => $entity->transactionId,
                'order_id' => $entity->orderId,
                'transaction_type' => $entity->transactionType->value,
                'status' => $entity->status->value,
            ],
        ];
    }

    public function composeFromModel(object $model): object
    {
    }
}
