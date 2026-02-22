<?php

use App\Enums\ChainTypeEnum;
use App\Enums\CurrenciesEnum;
use App\Enums\Transaction\TransactionTypeEnum;
use App\Enums\Transaction\TransactionStatusEnum;
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
        Schema::create('balance_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_account_id')->comment('One of many User\'s accounts');
            $table->foreign('source_account_id')->references('id')->on('accounts');
            $table->unsignedBigInteger('destination_account_id')->comment('One of many User\'s accounts');
            $table->foreign('destination_account_id')->references('id')->on('accounts');
            $table->enum('coin', array_column(CurrenciesEnum::cases(), 'value'))->comment('Fiat or Crypto currencies');
            $table->decimal('amount', 36, 18)->comment('Amount');

            $table->char('chain_name', 36)->nullable()->comment('TRON (TRC20) etc');
            $table->enum('chain_type', array_column(ChainTypeEnum::cases(), 'value'))->nullable()->comment('TRX');
            $table->string('address')->nullable()->comment('Crypto address');
            $table->string('transaction_id')->nullable()->comment('ID for transaction');
            $table->unsignedBigInteger('order_id')->nullable()->comment('Order ID');

            $table->enum('transaction_type', array_column(TransactionTypeEnum::cases(), 'value'))->comment('Money flow Deposit or Withdrawal');
            $table->enum('status', array_column(TransactionStatusEnum::cases(), 'value'))->default(TransactionStatusEnum::Request->value);
            $table->json('status_history')->nullable()->comment('Preserved status change history');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('balance_transactions');
    }
};
