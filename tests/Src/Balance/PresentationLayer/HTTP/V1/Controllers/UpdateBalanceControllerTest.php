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

    $this->actingAs($setup['actingUser'])
        ->post(route('update-balance'), $setup['transaction']->toArray())
        ->assertOk();

    $this->assertDatabaseCount('balance_transactions', 4);

    $validationService = new BalanceUpdateService(
        app(AccountStorageInterface::class),
        app(AccountRepositoryInterface::class),
    );
    $depositFee = $validationService->calculateDepositFee($operation['amount']);
    $withdrawalFee = $validationService->calculateWithdrawalFee($operation['amount']);

    $transactions = BalanceTransaction::query()
        ->where('transaction_id', $setup['transactionId'])->get();
    $this->assertCount(4, $transactions);

    $depositTransaction = $transactions->where('source_account_id', $setup['sourceAccount']->id)
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->first();
    $this->assertEquals($depositTransaction->amount, $operation['amount']);

    $withdrawalTransaction = $transactions->where('destination_account_id', $setup['destAccount']->id)
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->first();

    $this->assertEquals($withdrawalTransaction->amount, $operation['amount']);

//     Check balances (per UpdateBalanceUseCase: source balance -= amount + withdrawalFee, locked += withdrawalFee + amount; destination locked -= depositFee + amount)
    $setup['sourceAccount']->refresh();
    $this->assertEquals($operation['balance1'] - $operation['amount'] - $withdrawalFee, $setup['sourceAccount']->balance);
    $this->assertEquals($operation['locked_balance1'] + $withdrawalFee + $operation['amount'], $setup['sourceAccount']->locked_balance);

    $setup['destAccount']->refresh();
    $this->assertEquals($operation['locked_balance2'] - $depositFee + $operation['amount'], $setup['destAccount']->locked_balance);

    $this->assertEquals($depositFee + $withdrawalFee, $setup['platformAccount']->fresh()->locked_balance);
})->with([
    [['balance1' => 5000, 'locked_balance1' => 200, 'balance2' => 3000, 'locked_balance2' => 600, 'amount' => 1285.00, 'type' => TransactionTypeEnum::Deposit->value]],
]);

test('it lock correct amount of coins after Withdrawal transaction submit', function (array $operation) {
    $setup = createTransferTestSetup($operation);

    $this->actingAs($setup['actingUser'])
        ->post(route('update-balance'), $setup['transaction']->toArray())
        ->assertOk();

    $this->assertDatabaseCount('balance_transactions', 4);

    $validationService = new BalanceUpdateService(
        app(AccountStorageInterface::class),
        app(AccountRepositoryInterface::class),
    );
    $depositFee = $validationService->calculateDepositFee($operation['amount']);
    $withdrawalFee = $validationService->calculateWithdrawalFee($operation['amount']);

    $transactions = BalanceTransaction::query()
        ->where('transaction_id', $setup['transactionId'])->get();
    $this->assertCount(4, $transactions);

    // For Withdrawal: source is account2, destination is account1
    $withdrawalTransaction = $transactions->where('source_account_id', $setup['sourceAccount']->id)
        ->where('transaction_type', TransactionTypeEnum::Withdrawal->value)
        ->first();
    $this->assertEquals($withdrawalTransaction->amount, $operation['amount']);

    $depositTransaction = $transactions->where('destination_account_id', $setup['destAccount']->id)
        ->where('transaction_type', TransactionTypeEnum::Withdrawal->value)
        ->first();
    $this->assertEquals($depositTransaction->amount, $operation['amount']);

    // Check balances: source (account2) balance -= amount + withdrawalFee, locked += withdrawalFee + amount; dest (account1) locked -= depositFee + amount
    $setup['sourceAccount']->refresh();
    $this->assertEquals($operation['balance2'] - $operation['amount'] - $withdrawalFee, $setup['sourceAccount']->balance);
    $this->assertEquals($operation['locked_balance2'] + $withdrawalFee + $operation['amount'], $setup['sourceAccount']->locked_balance);

    $setup['destAccount']->refresh();
    $this->assertEquals($operation['locked_balance1'] - $depositFee + $operation['amount'], $setup['destAccount']->locked_balance);

    $this->assertEquals($depositFee + $withdrawalFee, $setup['platformAccount']->fresh()->locked_balance);
})->with([
    [['balance1' => 5000, 'locked_balance1' => 200, 'balance2' => 3000, 'locked_balance2' => 600, 'amount' => 1283.00, 'type' => TransactionTypeEnum::Withdrawal->value]],
]);
