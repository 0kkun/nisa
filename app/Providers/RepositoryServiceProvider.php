<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\GrowthPurchaseRepository;
use App\Repositories\GrowthPurchaseRepositoryInterface;
use App\Repositories\MarketPriceRepository;
use App\Repositories\MarketPriceRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(MarketPriceRepositoryInterface::class, MarketPriceRepository::class);
        $this->app->bind(GrowthPurchaseRepositoryInterface::class, GrowthPurchaseRepository::class);
    }
}
