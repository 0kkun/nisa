<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\GrowthPurchase;
use App\Services\Quota\GrowthQuotaServiceInterface;
use Illuminate\Support\Collection;
use Tests\Support\Fixtures;
use Tests\TestCase;

class PurchaseControllerTest extends TestCase
{
    public function test_購入履歴と枠の利用状況を表示する(): void
    {
        $purchase = new GrowthPurchase([
            'purchased_on' => '2026-04-07',
            'amount' => 600_000,
            'fund_name' => 'eMAXIS Slim 米国株式(S&P500)',
            'note' => '関税ショック',
        ]);
        $purchase->id = 1;

        $service = $this->createMock(GrowthQuotaServiceInterface::class);
        $service->method('summary')->willReturn(Fixtures::quota(usedThisYear: 900_000));
        $service->method('purchases')->willReturn(new Collection([$purchase]));
        $this->app->instance(GrowthQuotaServiceInterface::class, $service);

        $this->get('/purchases')
            ->assertOk()
            ->assertViewIs('purchases.index')
            ->assertSee('関税ショック')
            ->assertSee('600,000')
            ->assertSee('1,500,000');
    }

    public function test_購入を登録して一覧に戻る(): void
    {
        $service = $this->createMock(GrowthQuotaServiceInterface::class);
        $service->expects($this->once())
            ->method('record')
            ->with([
                'purchased_on' => '2026-10-10',
                'amount' => 300_000,
                'fund_name' => 'eMAXIS Slim 米国株式(S&P500)',
                'note' => null,
            ])
            ->willReturn(new GrowthPurchase);
        $this->app->instance(GrowthQuotaServiceInterface::class, $service);

        $this->post('/purchases', [
            'purchased_on' => '2026-10-10',
            'amount' => '300000',
            'fund_name' => 'eMAXIS Slim 米国株式(S&P500)',
            'note' => '',
        ])
            ->assertRedirect('/purchases')
            ->assertSessionHas('status');
    }

    public function test_入力エラーなら登録しない(): void
    {
        $service = $this->createMock(GrowthQuotaServiceInterface::class);
        $service->expects($this->never())->method('record');
        $this->app->instance(GrowthQuotaServiceInterface::class, $service);

        $this->from('/purchases')
            ->post('/purchases', ['purchased_on' => '', 'amount' => 0, 'fund_name' => ''])
            ->assertRedirect('/purchases')
            ->assertSessionHasErrors(['purchased_on', 'amount', 'fund_name']);
    }

    public function test_購入を削除して一覧に戻る(): void
    {
        $service = $this->createMock(GrowthQuotaServiceInterface::class);
        $service->expects($this->once())->method('remove')->with(3)->willReturn(true);
        $this->app->instance(GrowthQuotaServiceInterface::class, $service);

        $this->delete('/purchases/3')
            ->assertRedirect('/purchases')
            ->assertSessionHas('status');
    }

    public function test_存在しない購入の削除は404(): void
    {
        $service = $this->createMock(GrowthQuotaServiceInterface::class);
        $service->method('remove')->willReturn(false);
        $this->app->instance(GrowthQuotaServiceInterface::class, $service);

        $this->delete('/purchases/999')->assertNotFound();
    }
}
