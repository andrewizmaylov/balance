<?php

namespace Tests\Src\Balance\PresentationLayer\HTTP\V1\Controllers;

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;
use App\Enums\CurrenciesEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Enums\Transaction\TransactionTypeEnum;
use App\Models\Account;
use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Src\Balance\DomainLayer\Repository\AccountRepositoryInterface;
use Src\Balance\DomainLayer\Services\BalanceUpdateService;
use Src\Balance\DomainLayer\Storage\AccountStorageInterface;
use Src\Balance\InfrastructureLayer\Repository\BalanceTransactionRepository;

uses(RefreshDatabase::class);

/**
 * Creates transfer test setup: users, accounts, and transaction payload.
 * For Deposit: source=account1, destination=account2 (user1 deposits to user2).
 * For Withdrawal: source=account2, destination=account1 (user2 withdraws to user1).
 * Returns array with: user1, user2, platformAccount, account1, account2, sourceAccount, destAccount, transaction, transactionId.
 */
function createTransferTestSetup(array $operation): array
{
    $platform = User::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $platformAccount = Account::factory([
        'user_id' => $platform->id,
        'balance' => 0,
        'locked_balance' => 0,
        'coin' => CurrenciesEnum::BTC->value,
        'account_type' => AccountTypeEnum::BTC->value,
        'account_status' => AccountStatusEnum::Active->value,
    ])->create();

    $account1 = Account::factory([
        'user_id' => $user1->id,
        'balance' => $operation['balance1'],
        'locked_balance' => $operation['locked_balance1'],
        'coin' => CurrenciesEnum::BTC->value,
        'account_type' => AccountTypeEnum::BTC->value,
        'account_status' => AccountStatusEnum::Active->value,
    ])->create();

    $account2 = Account::factory([
        'user_id' => $user2->id,
        'balance' => $operation['balance2'],
        'locked_balance' => $operation['locked_balance2'],
        'coin' => CurrenciesEnum::BTC->value,
        'account_type' => AccountTypeEnum::BTC->value,
        'account_status' => AccountStatusEnum::Active->value,
    ])->create();

    $isWithdrawal = $operation['type'] === TransactionTypeEnum::Withdrawal->value;
    $sourceAccount = $isWithdrawal ? $account2 : $account1;
    $destAccount = $isWithdrawal ? $account1 : $account2;
    $actingUser = $isWithdrawal ? $user2 : $user1;

    $transactionId = Str::uuid7()->toString();

    $transaction = BalanceTransaction::factory([
        'source_account_id' => $sourceAccount->id,
        'destination_account_id' => $destAccount->id,
        'coin' => CurrenciesEnum::BTC->value,
        'amount' => $operation['amount'],
        'transaction_id' => $transactionId,
        'transaction_type' => $operation['type'],
        'status' => TransactionStatusEnum::Request->value,
    ])->make();

    return [
        'user1' => $user1,
        'user2' => $user2,
        'actingUser' => $actingUser,
        'platformAccount' => $platformAccount,
        'account1' => $account1,
        'account2' => $account2,
        'sourceAccount' => $sourceAccount,
        'destAccount' => $destAccount,
        'transaction' => $transaction,
        'transactionId' => $transactionId,
    ];
}

test('it lock correct amount of coins after Deposit transaction submit', function (array $operation) {
    $setup = createTransferTestSetup($operation);

    $response = $this->actingAs($setup['actingUser'])
        ->post(route('put-order'), $setup['transaction']->toArray());

    $response->assertOk();
    $response->assertJsonStructure([
        'id',
        'type',
        'attributes' => [
            'id',
            'source_account_id',
            'destination_account_id',
            'coin',
            'amount',
            'transaction_id',
            'transaction_type',
            'status',
        ],
    ]);
    $response->assertJsonPath('attributes.source_account_id', $setup['sourceAccount']->id);
    $response->assertJsonPath('attributes.destination_account_id', $setup['destAccount']->id);
    $this->assertEqualsWithDelta($operation['amount'], (float) $response->json('attributes.amount'), 0.01);
    $response->assertJsonPath('attributes.transaction_type', TransactionTypeEnum::Deposit->value);
    $response->assertJsonPath('attributes.coin', CurrenciesEnum::BTC->value);

    $this->assertDatabaseCount('balance_transactions', 4);

    $validationService = new BalanceUpdateService(
        app(AccountStorageInterface::class),
        app(AccountRepositoryInterface::class),
    );
    $depositFee = $validationService->calculateDepositFee($operation['amount']);
    $withdrawalFee = $validationService->calculateWithdrawalFee($operation['amount']);

    $transactions = BalanceTransaction::query()
        ->where('transaction_id', $setup['transactionId'])
        ->orderBy('id')
        ->get();
    $this->assertCount(4, $transactions);

    // All transactions share the same transaction_id and are stored as Pending
    foreach ($transactions as $tx) {
        $this->assertEquals($setup['transactionId'], $tx->transaction_id);
        $this->assertEquals(TransactionStatusEnum::Pending->value, $tx->status);
        $this->assertEquals(CurrenciesEnum::BTC->value, $tx->coin);
    }

    // 1. Main Deposit: source -> dest, amount
    $mainDeposit = $transactions->where('amount', $operation['amount'])
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->where('source_account_id', $setup['sourceAccount']->id)
        ->where('destination_account_id', $setup['destAccount']->id)
        ->first();
    $this->assertNotNull($mainDeposit, 'Main deposit transaction should exist');
    $this->assertEquals($operation['amount'], $mainDeposit->amount);

    // 2. Reverse Withdrawal: dest -> source, amount
    $reverseWithdrawal = $transactions->where('amount', $operation['amount'])
        ->where('transaction_type', TransactionTypeEnum::Withdrawal->value)
        ->where('source_account_id', $setup['destAccount']->id)
        ->where('destination_account_id', $setup['sourceAccount']->id)
        ->first();
    $this->assertNotNull($reverseWithdrawal, 'Reverse withdrawal transaction should exist');

    // 3. Fee Deposit: source -> platform, depositFee
    $feeDepositTx = $transactions->where('amount', $depositFee)
        ->where('destination_account_id', BalanceUpdateService::PLATFORM)
        ->first();
    $this->assertNotNull($feeDepositTx, 'Fee deposit transaction should exist');
    $this->assertEquals($setup['sourceAccount']->id, $feeDepositTx->source_account_id);
    $this->assertEquals(TransactionTypeEnum::Deposit->value, $feeDepositTx->transaction_type);

    // 4. Fee Withdrawal: dest -> platform, withdrawalFee
    $feeWithdrawalTx = $transactions->where('amount', $withdrawalFee)
        ->where('destination_account_id', BalanceUpdateService::PLATFORM)
        ->first();
    $this->assertNotNull($feeWithdrawalTx, 'Fee withdrawal transaction should exist');
    $this->assertEquals($setup['destAccount']->id, $feeWithdrawalTx->source_account_id);
    $this->assertEquals(TransactionTypeEnum::Deposit->value, $feeWithdrawalTx->transaction_type);

    // Balances: source balance -= amount + withdrawalFee, locked += withdrawalFee + amount
    $setup['sourceAccount']->refresh();
    $this->assertEquals($operation['balance1'] - $operation['amount'] - $withdrawalFee, $setup['sourceAccount']->balance);
    $this->assertEquals($operation['locked_balance1'] + $withdrawalFee + $operation['amount'], $setup['sourceAccount']->locked_balance);

    // Destination: only locked changes (locked -= depositFee + amount); balance unchanged
    $setup['destAccount']->refresh();
    $this->assertEquals($operation['balance2'], $setup['destAccount']->balance, 'Destination balance should remain unchanged');
    $this->assertEquals($operation['locked_balance2'] - $depositFee + $operation['amount'], $setup['destAccount']->locked_balance);

    // Platform: only locked increases, balance stays 0
    $platformAccount = $setup['platformAccount']->fresh();
    $this->assertEquals(0, $platformAccount->balance, 'Platform balance should remain 0');
    $this->assertEquals($depositFee + $withdrawalFee, $platformAccount->locked_balance);
})->with([
    [['balance1' => 5000, 'locked_balance1' => 200, 'balance2' => 3000, 'locked_balance2' => 600, 'amount' => 1285.00, 'type' => TransactionTypeEnum::Deposit->value]],
]);

test('it lock correct amount of coins after Withdrawal transaction submit', function (array $operation) {
    $setup = createTransferTestSetup($operation);

    $response = $this->actingAs($setup['actingUser'])
        ->post(route('put-order'), $setup['transaction']->toArray());

    $response->assertOk();
    $response->assertJsonStructure([
        'id',
        'type',
        'attributes' => [
            'id',
            'source_account_id',
            'destination_account_id',
            'coin',
            'amount',
            'transaction_id',
            'transaction_type',
            'status',
        ],
    ]);
    $response->assertJsonPath('attributes.source_account_id', $setup['sourceAccount']->id);
    $response->assertJsonPath('attributes.destination_account_id', $setup['destAccount']->id);
    $this->assertEqualsWithDelta($operation['amount'], (float) $response->json('attributes.amount'), 0.01);
    $response->assertJsonPath('attributes.transaction_type', TransactionTypeEnum::Withdrawal->value);
    $response->assertJsonPath('attributes.coin', CurrenciesEnum::BTC->value);

    $this->assertDatabaseCount('balance_transactions', 4);

    $validationService = new BalanceUpdateService(
        app(AccountStorageInterface::class),
        app(AccountRepositoryInterface::class),
    );
    $depositFee = $validationService->calculateDepositFee($operation['amount']);
    $withdrawalFee = $validationService->calculateWithdrawalFee($operation['amount']);

    $transactions = BalanceTransaction::query()
        ->where('transaction_id', $setup['transactionId'])
        ->orderBy('id')
        ->get();
    $this->assertCount(4, $transactions);

    // All transactions share the same transaction_id and are stored as Pending
    foreach ($transactions as $tx) {
        $this->assertEquals($setup['transactionId'], $tx->transaction_id);
        $this->assertEquals(TransactionStatusEnum::Pending->value, $tx->status);
        $this->assertEquals(CurrenciesEnum::BTC->value, $tx->coin);
    }

    // 1. Main Withdrawal: source (account2) -> dest (account1), amount
    $mainWithdrawal = $transactions->where('amount', $operation['amount'])
        ->where('transaction_type', TransactionTypeEnum::Withdrawal->value)
        ->where('source_account_id', $setup['sourceAccount']->id)
        ->where('destination_account_id', $setup['destAccount']->id)
        ->first();
    $this->assertNotNull($mainWithdrawal, 'Main withdrawal transaction should exist');
    $this->assertEquals($operation['amount'], $mainWithdrawal->amount);

    // 2. Reverse Deposit: dest -> source, amount
    $reverseDeposit = $transactions->where('amount', $operation['amount'])
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->where('source_account_id', $setup['destAccount']->id)
        ->where('destination_account_id', $setup['sourceAccount']->id)
        ->first();
    $this->assertNotNull($reverseDeposit, 'Reverse deposit transaction should exist');

    // 3. Fee Deposit: dest -> platform (for Withdrawal, feeDeposit source = original destination)
    $feeDepositTx = $transactions->where('amount', $depositFee)
        ->where('destination_account_id', BalanceUpdateService::PLATFORM)
        ->first();
    $this->assertNotNull($feeDepositTx, 'Fee deposit transaction should exist');
    $this->assertEquals($setup['destAccount']->id, $feeDepositTx->source_account_id);
    $this->assertEquals(TransactionTypeEnum::Deposit->value, $feeDepositTx->transaction_type);

    // 4. Fee Withdrawal: source -> platform (for Withdrawal, feeWithdrawal source = original source)
    $feeWithdrawalTx = $transactions->where('amount', $withdrawalFee)
        ->where('destination_account_id', BalanceUpdateService::PLATFORM)
        ->first();
    $this->assertNotNull($feeWithdrawalTx, 'Fee withdrawal transaction should exist');
    $this->assertEquals($setup['sourceAccount']->id, $feeWithdrawalTx->source_account_id);
    $this->assertEquals(TransactionTypeEnum::Deposit->value, $feeWithdrawalTx->transaction_type);

    // Balances: source (account2) balance -= amount + withdrawalFee, locked += withdrawalFee + amount
    $setup['sourceAccount']->refresh();
    $this->assertEquals($operation['balance2'] - $operation['amount'] - $withdrawalFee, $setup['sourceAccount']->balance);
    $this->assertEquals($operation['locked_balance2'] + $withdrawalFee + $operation['amount'], $setup['sourceAccount']->locked_balance);

    // Destination (account1): only locked changes; balance unchanged
    $setup['destAccount']->refresh();
    $this->assertEquals($operation['balance1'], $setup['destAccount']->balance, 'Destination balance should remain unchanged');
    $this->assertEquals($operation['locked_balance1'] - $depositFee + $operation['amount'], $setup['destAccount']->locked_balance);

    // Platform: only locked increases, balance stays 0
    $platformAccount = $setup['platformAccount']->fresh();
    $this->assertEquals(0, $platformAccount->balance, 'Platform balance should remain 0');
    $this->assertEquals($depositFee + $withdrawalFee, $platformAccount->locked_balance);
})->with([
    [['balance1' => 5000, 'locked_balance1' => 200, 'balance2' => 3000, 'locked_balance2' => 600, 'amount' => 1283.00, 'type' => TransactionTypeEnum::Withdrawal->value]],
]);
