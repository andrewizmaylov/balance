<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Responders;

use Exception;
use DomainDriven\BaseDomainStructure\Responder\PaginatedResult;
use DomainDriven\BaseDomainStructure\Responder\Contracts\ResponderInterface;
use Src\Balance\DomainLayer\Entities\BalanceTransaction;


class MakeWithdrawalResponder implements ResponderInterface
{
    public function composePaginatedResults(PaginatedResult $paginatedResults): PaginatedResult
    {
        $processedItems = array_map(fn ($record) => $this->composeEntity($record), $paginatedResults->items);

        $paginatedResults->withProcessedItems($processedItems);

        return $paginatedResults;
    }

    public function composeEntity(object $entity): array
    {
        if (! $entity instanceof BalanceTransaction) {
            throw new Exception('Received unsupported entity ' . BalanceTransaction::class);
        }

        return [
            'id' => $entity->id,
            'type' => 'BalanceTransaction',
            'attributes' => [
                'id' => $entity->id,
                'account_id' => $entity->account->id,
                'coin' => $entity->coin->value,
                'amount' => $entity->amount,
                'fee' => $entity->fee,
                'chain_name' => $entity->chainName,
                'chain_type' => $entity->chainType->value,
                'address' => $entity->address,
                'transaction_id' => $entity->transactionId,
                'order_id' => $entity->orderId,
                'transaction_type' => $entity->transactionType->value,
                'status' => $entity->status->value,
            ],
        ];
    }

    public function composeFromModel(object $model)
    {
    }
}
