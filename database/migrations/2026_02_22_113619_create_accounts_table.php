<?php

use App\Enums\Account\AccountStatusEnum;
use App\Enums\Account\AccountTypeEnum;
use App\Enums\CurrenciesEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->decimal('balance', 36, 18)->default(0);
            $table->decimal('locked_balance', 36, 18)->default(0);
            $table->enum('coin', array_column(CurrenciesEnum::cases(), 'value'))->comment('Fiat or Crypto currencies');
            $table->enum('account_type', array_column(AccountTypeEnum::cases(), 'value'));
            $table->enum('account_status', array_column(AccountStatusEnum::cases(), 'value'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
