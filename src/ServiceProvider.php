<?php

declare(strict_types=1);

namespace Src;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use Src\Balance\DomainLayer\Repository\AccountRepositoryInterface;
use Src\Balance\DomainLayer\Repository\BalanceTransactionRepositoryInterface;
use Src\Balance\DomainLayer\Storage\AccountStorageInterface;
use Src\Balance\DomainLayer\Storage\BalanceTransactionStorageInterface;
use Src\Balance\InfrastructureLayer\Repository\AccountRepository;
use Src\Balance\InfrastructureLayer\Repository\BalanceTransactionRepository;
use Src\Balance\InfrastructureLayer\Storage\AccountStorage;
use Src\Balance\InfrastructureLayer\Storage\BalanceTransactionStorage;

class ServiceProvider extends IlluminateServiceProvider
{
    protected array $middlewareGroups = [
        'auth:sanctum',
    ];

    protected array $openMiddleware = [
      //
    ];

    /**
     * Register domain service providers.
     */
    public function register(): void
    {
        $this->registerContracts();
    }

    /**
     * Load routes after registering all services.
     */
    public function boot(): void
    {
        $this->registerRoutes();
    }

    public function registerRoutes(): void
    {
        Route::middleware($this->openMiddleware)->group(__DIR__ . '/Balance/PresentationLayer/HTTP/V1/routes.php');
    }

    public function registerContracts(): void
    {
        $this->app->bind(BalanceTransactionRepositoryInterface::class, BalanceTransactionRepository::class);
        $this->app->bind(BalanceTransactionStorageInterface::class, BalanceTransactionStorage::class);

        $this->app->bind(AccountRepositoryInterface::class, AccountRepository::class);
        $this->app->bind(AccountStorageInterface::class, AccountStorage::class);
    }
}
