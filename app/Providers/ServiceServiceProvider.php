<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Timing\InvestmentPlanner;
use App\Domain\Timing\MarketSnapshotBuilder;
use App\Domain\Timing\TimingScorer;
use App\Repositories\GrowthPurchaseRepositoryInterface;
use App\Repositories\MarketPriceRepositoryInterface;
use App\Services\Backtest\BacktestService;
use App\Services\Backtest\BacktestServiceInterface;
use App\Services\MarketData\MarketDataClientInterface;
use App\Services\MarketData\MarketDataService;
use App\Services\MarketData\MarketDataServiceInterface;
use App\Services\MarketData\YahooFinanceClient;
use App\Services\Quota\GrowthQuotaService;
use App\Services\Quota\GrowthQuotaServiceInterface;
use App\Services\Timing\TimingService;
use App\Services\Timing\TimingServiceInterface;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerDomain();

        $this->app->bind(MarketDataClientInterface::class, YahooFinanceClient::class);
        $this->app->bind(MarketDataServiceInterface::class, MarketDataService::class);

        $this->app->bind(GrowthQuotaServiceInterface::class, fn (Application $app) => new GrowthQuotaService(
            $app->make(GrowthPurchaseRepositoryInterface::class),
            annualLimit: (int) config('nisa.growth_annual_limit'),
            lifetimeLimit: (int) config('nisa.growth_lifetime_limit'),
        ));

        $this->app->bind(TimingServiceInterface::class, fn (Application $app) => new TimingService(
            $app->make(MarketPriceRepositoryInterface::class),
            $app->make(GrowthQuotaServiceInterface::class),
            $app->make(MarketSnapshotBuilder::class),
            $app->make(TimingScorer::class),
            $app->make(InvestmentPlanner::class),
            historyDays: (int) config('nisa.history_days'),
        ));

        $this->app->bind(BacktestServiceInterface::class, fn (Application $app) => new BacktestService(
            $app->make(MarketPriceRepositoryInterface::class),
            $app->make(MarketSnapshotBuilder::class),
            $app->make(TimingScorer::class),
            $app->make(InvestmentPlanner::class),
            annualLimit: (int) config('nisa.growth_annual_limit'),
        ));
    }

    /**
     * 設定値を持つ Domain クラス
     */
    private function registerDomain(): void
    {
        $this->app->singleton(MarketSnapshotBuilder::class, fn () => new MarketSnapshotBuilder(
            highWindowDays: (int) config('nisa.high_window_days'),
            movingAverageDays: (int) config('nisa.moving_average_days'),
        ));

        $this->app->singleton(TimingScorer::class, fn () => new TimingScorer(
            scoring: config('nisa.scoring'),
            levels: config('nisa.levels'),
        ));

        $this->app->singleton(InvestmentPlanner::class, fn () => new InvestmentPlanner(
            roundingUnit: (int) config('nisa.rounding_unit'),
        ));
    }
}
