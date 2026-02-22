<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTransaction extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'status_history' => 'array',
    ];

    protected $guarded = ['id'];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
