<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\UpdateBalanceController;

Route::prefix('public/api/v1/BalanceTransaction')
    ->group(function () {
        Route::post('update-balance', UpdateBalanceController::class)->name('update-balance');
    });
