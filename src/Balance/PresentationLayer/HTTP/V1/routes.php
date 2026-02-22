<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\MakeDepositController;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\MakeWithdrawalController;

Route::prefix('public/api/v1/BalanceTransaction')
    ->group(function () {
        Route::post('make-deposit', MakeDepositController::class)->name('make-deposit');
        Route::post('make-withdrawal', MakeWithdrawalController::class)->name('make-withdrawal');
    });
