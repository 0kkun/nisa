<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Services\Backtest\BacktestServiceInterface;
use Tests\Support\Fixtures;
use Tests\TestCase;

class BacktestControllerTest extends TestCase
{
    public function test_指定した期間でバックテストした結果を表示する(): void
    {
        $service = $this->createMock(BacktestServiceInterface::class);
        $service->method('availableYears')->willReturn([1997, 2025]);
        $service->expects($this->once())->method('run')->with(2010, 2020)->willReturn(Fixtures::backtest());
        $this->app->instance(BacktestServiceInterface::class, $service);

        $this->get('/backtest?from_year=2010&to_year=2020')
            ->assertOk()
            ->assertViewIs('backtest.index')
            ->assertViewHas('result')
            ->assertSee('スコア連動')
            ->assertSee('77,022,000')
            ->assertSee('2020-12-31');
    }

    public function test_期間を省略したら全期間で実行する(): void
    {
        $service = $this->createMock(BacktestServiceInterface::class);
        $service->method('availableYears')->willReturn([1997, 2025]);
        $service->expects($this->once())->method('run')->with(null, null)->willReturn(Fixtures::backtest());
        $this->app->instance(BacktestServiceInterface::class, $service);

        $this->get('/backtest')->assertOk();
    }

    public function test_データ不足なら取得方法を案内する(): void
    {
        $service = $this->createMock(BacktestServiceInterface::class);
        $service->method('availableYears')->willReturn(null);
        $service->method('run')->willReturn(null);
        $this->app->instance(BacktestServiceInterface::class, $service);

        $this->get('/backtest')
            ->assertOk()
            ->assertSee('make fetch');
    }

    public function test_不正な年はエラー(): void
    {
        $service = $this->createMock(BacktestServiceInterface::class);
        $service->expects($this->never())->method('run');
        $this->app->instance(BacktestServiceInterface::class, $service);

        $this->from('/backtest')
            ->get('/backtest?from_year=abc')
            ->assertRedirect('/backtest')
            ->assertSessionHasErrors('from_year');
    }
}
