<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Requests;

use App\Enums\ChainTypeEnum;
use App\Enums\CurrenciesEnum;
use App\Enums\Transaction\TransactionStatusEnum;
use App\Enums\Transaction\TransactionTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class MakeWithdrawalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /*
    protected function prepareForValidation(): void
    {
        $this->merge([
            'transportation_id' => $this->route('transportation_id'),
        ]);
    }
    */

    public function rules(): array
    {
        return [
            'account_id' => 'required|integer|exists:accounts,id',
            'coin' => ['required', new Enum(CurrenciesEnum::class)],
            'amount' => 'required|numeric|gt:0',
            'chain_name' => 'nullable|string|max:36',
            'chain_type' => ['nullable', new Enum(ChainTypeEnum::class)],
            'address' => 'nullable|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'order_id' => 'required|int|gt:0',
            'transaction_type' => ['nullable', new Enum(TransactionTypeEnum::class)],
            'status' => ['nullable', new Enum(TransactionStatusEnum::class)],
        ];
    }
}
