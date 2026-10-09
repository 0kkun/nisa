<?php

declare(strict_types=1);

namespace Tests\Feature\Commands;

use App\Services\MarketData\MarketDataServiceInterface;
use RuntimeException;
use Tests\TestCase;

class FetchMarketDataCommandTest extends TestCase
{
    public function test_市場データを取得して件数を表示する(): void
    {
        $service = $this->createMock(MarketDataServiceInterface::class);
        $service->expects($this->once())->method('sync')->willReturn(['^GSPC' => 5, '^VIX' => 5, 'JPY=X' => 6]);
        $this->app->instance(MarketDataServiceInterface::class, $service);

        $this->artisan('market:fetch')
            ->expectsOutputToContain('S&P500')
            ->expectsOutputToContain('ドル円')
            ->assertSuccessful();
    }

    public function test_取得に失敗したら異常終了する(): void
    {
        $service = $this->createMock(MarketDataServiceInterface::class);
        $service->method('sync')->willThrowException(new RuntimeException('S&P500 の取得に失敗しました (HTTP 429)'));
        $this->app->instance(MarketDataServiceInterface::class, $service);

        $this->artisan('market:fetch')
            ->expectsOutputToContain('HTTP 429')
            ->assertFailed();
    }
}
