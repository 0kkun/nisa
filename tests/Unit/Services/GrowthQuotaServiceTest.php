<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\GrowthPurchase;
use App\Repositories\GrowthPurchaseRepositoryInterface;
use App\Services\Quota\GrowthQuotaService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GrowthQuotaServiceTest extends TestCase
{
    public function test_今年の残り枠と残り月数を計算する(): void
    {
        $repository = $this->createMock(GrowthPurchaseRepositoryInterface::class);
        $repository->expects($this->once())->method('sumAmountByYear')->with(2026)->willReturn(900_000);
        $repository->method('sumAmount')->willReturn(3_000_000);

        $summary = $this->service($repository)->summary(CarbonImmutable::parse('2026-10-10'));

        $this->assertSame(2026, $summary->year);
        $this->assertSame(2_400_000, $summary->annualLimit);
        $this->assertSame(900_000, $summary->usedThisYear);
        $this->assertSame(1_500_000, $summary->remainingThisYear);
        $this->assertSame(12_000_000, $summary->lifetimeLimit);
        $this->assertSame(9_000_000, $summary->remainingLifetime);
        // 10月・11月・12月
        $this->assertSame(3, $summary->monthsLeft);
        $this->assertSame(1_500_000, $summary->investable);
    }

    public function test_生涯枠の残りが少なければ投資可能額は生涯枠の残り(): void
    {
        $repository = $this->createMock(GrowthPurchaseRepositoryInterface::class);
        $repository->method('sumAmountByYear')->willReturn(0);
        $repository->method('sumAmount')->willReturn(11_500_000);

        $summary = $this->service($repository)->summary(CarbonImmutable::parse('2026-01-01'));

        $this->assertSame(2_400_000, $summary->remainingThisYear);
        $this->assertSame(500_000, $summary->investable);
        $this->assertSame(12, $summary->monthsLeft);
    }

    public function test_年間枠を超えて買っていても残りはマイナスにならない(): void
    {
        $repository = $this->createMock(GrowthPurchaseRepositoryInterface::class);
        $repository->method('sumAmountByYear')->willReturn(2_500_000);
        $repository->method('sumAmount')->willReturn(2_500_000);

        $summary = $this->service($repository)->summary(CarbonImmutable::parse('2026-06-01'));

        $this->assertSame(0, $summary->remainingThisYear);
        $this->assertSame(0, $summary->investable);
    }

    public function test_購入履歴の取得_登録_削除はリポジトリに委譲する(): void
    {
        $purchase = new GrowthPurchase(['amount' => 100_000]);
        $repository = $this->createMock(GrowthPurchaseRepositoryInterface::class);
        $repository->method('all')->willReturn(new Collection([$purchase]));
        $repository->expects($this->once())->method('create')->with(['amount' => 100_000])->willReturn($purchase);
        $repository->expects($this->once())->method('delete')->with(5)->willReturn(true);

        $service = $this->service($repository);

        $this->assertSame([$purchase], $service->purchases()->all());
        $this->assertSame($purchase, $service->record(['amount' => 100_000]));
        $this->assertTrue($service->remove(5));
    }

    private function service(GrowthPurchaseRepositoryInterface $repository): GrowthQuotaService
    {
        return new GrowthQuotaService($repository, annualLimit: 2_400_000, lifetimeLimit: 12_000_000);
    }
}
