<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Services\Timing\TimingServiceInterface;
use Carbon\CarbonImmutable;
use Tests\Support\Fixtures;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-10-10 09:00:00'));
    }

    public function test_買い場の判定と推奨投入額を表示する(): void
    {
        $service = $this->createMock(TimingServiceInterface::class);
        $service->expects($this->once())
            ->method('assess')
            ->with($this->callback(fn (CarbonImmutable $today) => $today->format('Y-m-d') === '2026-10-10'))
            ->willReturn(Fixtures::assessment());
        $this->app->instance(TimingServiceInterface::class, $service);

        $response = $this->get('/');

        $response->assertOk()
            ->assertViewIs('dashboard')
            ->assertViewHas('assessment')
            ->assertSee('強い買い場')
            ->assertSee('1,500,000')
            ->assertSee('-15.0%')
            ->assertSee('2026-10-09');
    }

    public function test_市場データがなければ取得方法を案内する(): void
    {
        $service = $this->createMock(TimingServiceInterface::class);
        $service->method('assess')->willReturn(null);
        $this->app->instance(TimingServiceInterface::class, $service);

        $this->get('/')
            ->assertOk()
            ->assertSee('make fetch');
    }
}
