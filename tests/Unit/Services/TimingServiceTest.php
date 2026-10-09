<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Quota\QuotaSummary;
use App\Domain\Timing\InvestmentPlanner;
use App\Domain\Timing\MarketSnapshotBuilder;
use App\Domain\Timing\TimingScorer;
use App\Enums\MarketSymbol;
use App\Repositories\MarketPriceRepositoryInterface;
use App\Services\Quota\GrowthQuotaServiceInterface;
use App\Services\Timing\TimingService;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class TimingServiceTest extends TestCase
{
    public function test_最新日の指標とスコアと推奨投入額を返す(): void
    {
        $repository = $this->createMock(MarketPriceRepositoryInterface::class);
        $repository->method('getCloses')->willReturnCallback(fn (MarketSymbol $symbol) => match ($symbol) {
            // 高値 100 → 85 で -15%（3点）
            MarketSymbol::Sp500 => ['2026-10-07' => 100.0, '2026-10-08' => 95.0, '2026-10-09' => 85.0],
            // 25 以上（2点）
            MarketSymbol::Vix => ['2026-10-09' => 26.0],
            MarketSymbol::UsdJpy => ['2026-10-09' => 150.0],
        });

        $assessment = $this->service($repository, $this->quota(investable: 1_500_000, monthsLeft: 3))
            ->assess(CarbonImmutable::parse('2026-10-10'));

        $this->assertNotNull($assessment);
        $this->assertSame('2026-10-09', $assessment->snapshot->date);
        $this->assertSame(5, $assessment->score->total);
        $this->assertSame('buy', $assessment->score->level->key);
        // 1,500,000 / 3 × 2.0 = 1,000,000
        $this->assertSame(1_000_000, $assessment->recommendedAmount);
        $this->assertSame(1_500_000, $assessment->quota->investable);
    }

    public function test_履歴は古い順に指定日数分を返す(): void
    {
        $repository = $this->createMock(MarketPriceRepositoryInterface::class);
        $repository->method('getCloses')->willReturnCallback(fn (MarketSymbol $symbol) => match ($symbol) {
            MarketSymbol::Sp500 => ['2026-10-07' => 100.0, '2026-10-08' => 95.0, '2026-10-09' => 85.0],
            default => [],
        });

        $assessment = $this->service($repository, $this->quota(1_000_000, 3), historyDays: 2)
            ->assess(CarbonImmutable::parse('2026-10-10'));

        $this->assertSame(['2026-10-08', '2026-10-09'], array_map(fn ($point) => $point->snapshot->date, $assessment->history));
        $this->assertSame(3, $assessment->history[1]->score->total);
    }

    public function test_データがなければnull(): void
    {
        $repository = $this->createMock(MarketPriceRepositoryInterface::class);
        $repository->method('getCloses')->willReturn([]);

        $this->assertNull($this->service($repository, $this->quota(0, 1))->assess(CarbonImmutable::parse('2026-10-10')));
    }

    private function service(MarketPriceRepositoryInterface $repository, QuotaSummary $quota, int $historyDays = 500): TimingService
    {
        $quotaService = $this->createMock(GrowthQuotaServiceInterface::class);
        $quotaService->method('summary')->willReturn($quota);

        return new TimingService(
            $repository,
            $quotaService,
            new MarketSnapshotBuilder(highWindowDays: 252, movingAverageDays: 200),
            new TimingScorer(
                scoring: [
                    'drawdown' => [[-20, 4], [-15, 3], [-10, 2], [-5, 1]],
                    'ma_deviation' => [[-10, 3], [-5, 2], [0, 1]],
                    'vix' => [[30, 3], [25, 2], [20, 1]],
                    'usd_jpy_deviation' => [[0, 1]],
                ],
                levels: [
                    ['key' => 'strong', 'label' => '強い買い場', 'min_score' => 6, 'multiplier' => 3.0],
                    ['key' => 'buy', 'label' => '買い増し', 'min_score' => 3, 'multiplier' => 2.0],
                    ['key' => 'normal', 'label' => '通常', 'min_score' => 0, 'multiplier' => 0.5],
                ],
            ),
            new InvestmentPlanner(roundingUnit: 10_000),
            historyDays: $historyDays,
        );
    }

    private function quota(int $investable, int $monthsLeft): QuotaSummary
    {
        return new QuotaSummary(
            year: 2026,
            annualLimit: 2_400_000,
            usedThisYear: 2_400_000 - $investable,
            remainingThisYear: $investable,
            lifetimeLimit: 12_000_000,
            usedLifetime: 2_400_000 - $investable,
            remainingLifetime: 12_000_000 - (2_400_000 - $investable),
            monthsLeft: $monthsLeft,
            investable: $investable,
        );
    }
}
