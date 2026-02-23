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

test('it lock correct amount of coins after transaction submit', function (array $operation) {
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

    $this->actingAs($user1);
    $transactionId = Str::uuid7()->toString();
    $transaction = BalanceTransaction::factory([
        'source_account_id' => $account1->id,
        'destination_account_id' => $account2->id,
        'coin' => CurrenciesEnum::BTC->value,
        'amount' => $operation['amount'],
        'transaction_id' => $transactionId,
        'transaction_type' => TransactionTypeEnum::Deposit->value,
        'status' => TransactionStatusEnum::Request->value,
    ])->make();

    $this->actingAs($user1)
        ->post(route('update-balance'), $transaction->toArray())
        ->assertOk();

    $this->assertDatabaseCount('balance_transactions', 4);

    $validationService = new BalanceUpdateService(
        app(AccountStorageInterface::class),
        app(AccountRepositoryInterface::class),
    );
    $depositFee = $validationService->calculateDepositFee($operation['amount']);
    $withdrawalFee = $validationService->calculateWithdrawalFee($operation['amount']);

    $transactions = BalanceTransaction::query()
        ->where('transaction_id', $transactionId)->get();
    $this->assertCount(4, $transactions);

    $depositTransaction = $transactions->where('source_account_id', $account1->id)
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->first();
    $this->assertEquals($depositTransaction->amount, $operation['amount']);

    $withdrawalTransaction = $transactions->where('destination_account_id', $account2->id)
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->first();

    $this->assertEquals($withdrawalTransaction->amount, $operation['amount']);

    // Check balances (per UpdateBalanceUseCase: source balance -= amount + withdrawalFee, locked += withdrawalFee + amount; destination locked -= depositFee + amount)
    $account1->refresh();
    $this->assertEquals($operation['balance1'] - $operation['amount'] - $withdrawalFee, $account1->balance);
    $this->assertEquals($operation['locked_balance1'] + $withdrawalFee + $operation['amount'], $account1->locked_balance);

    $account2->refresh();
    $this->assertEquals($operation['locked_balance2'] - $depositFee + $operation['amount'], $account2->locked_balance);

    $this->assertEquals($depositFee + $withdrawalFee, $platformAccount->fresh()->locked_balance);

})->with([
    [['balance1' => 5000, 'locked_balance1' => 200, 'balance2' => 3000, 'locked_balance2' => 600, 'amount' => 1285.00, ]],
]);
