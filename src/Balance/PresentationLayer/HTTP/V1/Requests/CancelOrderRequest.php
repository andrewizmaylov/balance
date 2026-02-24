<?php

declare(strict_types=1);

namespace Src\Balance\PresentationLayer\HTTP\V1\Requests;

use App\Enums\Transaction\TransactionStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'transaction_id' => $this->route('transaction_id'),
        ]);
    }

    public function rules(): array
    {
        return [
            'transaction_id' => 'required|string|max:255',
        ];
    }
}
