<?php

declare(strict_types=1);

namespace Src;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

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
        $this->app->bind(\Src\Balance\DomainLayer\Repository\BalanceRepositoryInterface::class, \Src\Balance\InfrastructureLayer\Repository\BalanceRepository::class);
        $this->app->bind(\Src\Balance\DomainLayer\Storage\BalanceStorageInterface::class, \Src\Balance\InfrastructureLayer\Storage\BalanceStorage::class);
    }
}
