<?php

namespace Database\Factories;

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;
use App\Enums\CurrenciesEnum;
use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    protected $model = Account::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'balance' => '0',
            'locked_balance' => '0',
            'coin' => fake()->randomElement(CurrenciesEnum::cases())->value,
            'account_type' => fake()->randomElement(AccountTypeEnum::cases())->value,
            'account_status' => fake()->randomElement(AccountStatusEnum::cases())->value,
        ];
    }

    public function forUser(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $user->id,
        ]);
    }
}
