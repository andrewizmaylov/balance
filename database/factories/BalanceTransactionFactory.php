<?php

namespace Database\Factories;

use App\Enums\CurrenciesEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Enums\Transaction\TransactionTypeEnum;
use App\Models\Account;
use App\Models\BalanceTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BalanceTransaction>
 */
class BalanceTransactionFactory extends Factory
{
    protected $model = BalanceTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'source_account_id' => Account::factory(),
            'destination_account_id' => Account::factory(),
            'coin' => fake()->randomElement(CurrenciesEnum::cases())->value,
            'amount' => fake()->randomFloat(18, 0.001, 1000),
            'chain_name' => null,
            'chain_type' => null,
            'address' => null,
            'transaction_id' => null,
            'order_id' => null,
            'transaction_type' => fake()->randomElement(TransactionTypeEnum::cases())->value,
            'status' => TransactionStatusEnum::Request->value,
            'status_history' => null,
        ];
    }

    public function forAccount(Account $account): static
    {
        return $this->state(fn (array $attributes) => [
            'account_id' => $account->id,
        ]);
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => TransactionTypeEnum::Deposit->value,
        ]);
    }

    public function withdrawal(): static
    {
        return $this->state(fn (array $attributes) => [
            'transaction_type' => TransactionTypeEnum::Withdrawal->value,
        ]);
    }
}
