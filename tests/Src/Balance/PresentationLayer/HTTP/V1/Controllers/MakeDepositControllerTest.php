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
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Src\Balance\DomainLayer\Repository\BalanceTransactionRepositoryInterface;
use Src\Balance\DomainLayer\Services\BalanceValidationService;
use Src\Balance\DomainLayer\Storage\BalanceTransactionStorageInterface;
use Src\Balance\InfrastructureLayer\Repository\BalanceTransactionRepository;
use Src\Balance\InfrastructureLayer\Storage\BalanceTransactionStorage;

uses(RefreshDatabase::class);

uses(RefreshDatabase::class);

beforeEach(function () {
    app()->bind(BalanceTransactionRepositoryInterface::class, BalanceTransactionRepository::class);
    app()->bind(BalanceTransactionStorageInterface::class, BalanceTransactionStorage::class);
    app()->bind(ConnectionInterface::class, fn () => DB::connection());
});


test('make deposit', function (array $operation) {
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
        ->post(route('make-deposit'), $transaction->toArray())
        ->assertOk();

    $this->assertDatabaseCount('balance_transactions', 6);

    $validationService = new BalanceValidationService();
    $depositFee = $validationService->calculateDepositFee($operation['amount']);
    $withdrawalFee = $validationService->calculateDepositFee($operation['amount']);

    $transactions = BalanceTransaction::query()
        ->where('transaction_id', $transactionId)->get();
    $this->assertCount(6, $transactions);

    $depositTransaction = $transactions->where('source_account_id', $account1->id)
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->first();
    $this->assertEquals($depositTransaction->amount, $operation['amount']);

    $withdrawalTransaction = $transactions->where('destination_account_id', $account2->id)
        ->where('transaction_type', TransactionTypeEnum::Deposit->value)
        ->first();

    $this->assertEquals($withdrawalTransaction->amount, $operation['amount']);

    // Check balances
    $account1 = Account::query()->findOrFail($account1->id);
//    dd($account1);
    $this->assertEquals($account1->locked_balance - $operation['amount'] + $withdrawalFee, $account1->locked_balance);

//    $account2 = Account::query()->findOrFail($account2->id);
//    $this->assertEquals($operation['locked_balance2'] + $operation['amount'] - $depositFee, $account2->balance);
})->with([
    [['balance1' => 5000, 'locked_balance1' => 200, 'balance2' => 3000, 'locked_balance2' => 600, 'amount' => 1285.00, ]],
]);
