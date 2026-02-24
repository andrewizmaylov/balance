<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\CancelOrderController;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\CompleteOrderController;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\DisputeOrderController;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\PutOrderController;
use Src\Balance\PresentationLayer\HTTP\V1\Controllers\ReleaseCoinsController;

Route::prefix('public/api/v1/BalanceTransaction')
    ->group(function () {
        Route::post('put-order', PutOrderController::class)->name('put-order');
        Route::patch('cancel-order/{transaction_id}', CancelOrderController::class)->name('cancel-order');
        Route::patch('complete-order/{transaction_id}', CompleteOrderController::class)->name('complete-order');
        Route::patch('dispute-order/{transaction_id}', DisputeOrderController::class)->name('dispute-order');
        Route::patch('release-coins/{transaction_id}', ReleaseCoinsController::class)->name('release-coins');
    });
