<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\GrowthPurchase;
use App\Repositories\GrowthPurchaseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrowthPurchaseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private GrowthPurchaseRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new GrowthPurchaseRepository;
    }

    public function test_購入履歴を購入日の新しい順に返す(): void
    {
        $old = GrowthPurchase::factory()->create(['purchased_on' => '2026-01-10']);
        $new = GrowthPurchase::factory()->create(['purchased_on' => '2026-03-10']);

        $this->assertSame([$new->id, $old->id], $this->repository->all()->pluck('id')->all());
    }

    public function test_購入履歴を登録する(): void
    {
        $purchase = $this->repository->create([
            'purchased_on' => '2026-04-01',
            'amount' => 300_000,
            'fund_name' => 'eMAXIS Slim 米国株式(S&P500)',
            'note' => '急落時',
        ]);

        $this->assertSame(300_000, $purchase->amount);
        $this->assertDatabaseHas('growth_purchases', ['amount' => 300_000, 'note' => '急落時']);
    }

    public function test_購入履歴を削除する(): void
    {
        $purchase = GrowthPurchase::factory()->create();

        $this->assertTrue($this->repository->delete($purchase->id));
        $this->assertDatabaseMissing('growth_purchases', ['id' => $purchase->id]);
    }

    public function test_存在しない_i_dの削除はfalse(): void
    {
        $this->assertFalse($this->repository->delete(999));
    }

    public function test_指定年の購入額合計(): void
    {
        GrowthPurchase::factory()->create(['purchased_on' => '2025-12-31', 'amount' => 100_000]);
        GrowthPurchase::factory()->create(['purchased_on' => '2026-01-01', 'amount' => 200_000]);
        GrowthPurchase::factory()->create(['purchased_on' => '2026-12-31', 'amount' => 300_000]);

        $this->assertSame(500_000, $this->repository->sumAmountByYear(2026));
        $this->assertSame(0, $this->repository->sumAmountByYear(2024));
    }

    public function test_全期間の購入額合計(): void
    {
        GrowthPurchase::factory()->create(['amount' => 100_000]);
        GrowthPurchase::factory()->create(['amount' => 250_000]);

        $this->assertSame(350_000, $this->repository->sumAmount());
    }
}
