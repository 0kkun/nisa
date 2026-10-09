<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Domain\Timing\InvestmentPlanner;
use App\Domain\Timing\MarketSnapshotBuilder;
use App\Domain\Timing\TimingScorer;
use App\Enums\MarketSymbol;
use App\Repositories\MarketPriceRepositoryInterface;
use App\Services\Backtest\BacktestService;
use Tests\TestCase;

class BacktestServiceTest extends TestCase
{
    /**
     * 月初の S&P500（ドル円は常に 100 なので円建ては ×100）
     * 4月だけ高値 120 から -25% の急落
     */
    private const SP500 = [
        '2023-12-01' => 100.0,
        '2024-01-02' => 100.0, '2024-02-01' => 110.0, '2024-03-01' => 120.0, '2024-04-01' => 90.0,
        '2024-05-01' => 120.0, '2024-06-03' => 120.0, '2024-07-01' => 120.0, '2024-08-01' => 120.0,
        '2024-09-03' => 120.0, '2024-10-01' => 120.0, '2024-11-01' => 120.0, '2024-12-02' => 120.0,
    ];

    public function test_一括_毎月均等_スコア連動の3戦略を比較する(): void
    {
        $result = $this->service($this->repository(self::SP500))->run(2024, 2024);

        $this->assertNotNull($result);
        $this->assertSame(2024, $result->fromYear);
        $this->assertSame(2024, $result->toYear);
        $this->assertSame('2024-12-02', $result->valuationDate);

        foreach (['lump', 'monthly', 'timing'] as $key) {
            $this->assertSame(1_200_000, $result->strategies[$key]->invested, $key);
        }

        // 一括: 1月に 1,200,000 円 / 10,000 = 120口 → 12月 12,000 円
        $this->assertEqualsWithDelta(120.0, $result->strategies['lump']->units, 1e-9);
        $this->assertEqualsWithDelta(1_440_000.0, $result->strategies['lump']->finalValue, 1e-6);
        $this->assertEqualsWithDelta(10_000.0, $result->strategies['lump']->averageCost, 1e-6);
        $this->assertEqualsWithDelta(20.0, $result->strategies['lump']->returnPct, 1e-9);

        // 毎月均等: 100,000 円ずつ
        $monthlyUnits = 10 + 100_000 / 11_000 + 100_000 / 12_000 + 100_000 / 9_000 + 8 * 100_000 / 12_000;
        $this->assertEqualsWithDelta($monthlyUnits, $result->strategies['monthly']->units, 1e-9);

        // スコア連動: 通常月は 0.5 倍、4月（-25% で4点）は 3 倍、12月は残り全額
        $timingUnits = 50_000 / 10_000 + 50_000 / 11_000 + 50_000 / 12_000 + 350_000 / 9_000 + 700_000 / 12_000;
        $this->assertEqualsWithDelta($timingUnits, $result->strategies['timing']->units, 1e-9);
        $this->assertEqualsWithDelta($timingUnits * 12_000, $result->strategies['timing']->finalValue, 1e-6);
    }

    public function test_スコア連動の毎月の投入額を記録する(): void
    {
        $result = $this->service($this->repository(self::SP500))->run(2024, 2024);

        $amounts = array_map(fn ($purchase) => $purchase->amount, $result->timingPurchases);
        $this->assertSame(
            [50_000, 50_000, 50_000, 350_000, 40_000, 40_000, 50_000, 50_000, 60_000, 70_000, 90_000, 300_000],
            $amounts,
        );
        $this->assertSame('2024-04-01', $result->timingPurchases[3]->date);
        $this->assertSame('strong', $result->timingPurchases[3]->levelKey);
        $this->assertSame(4, $result->timingPurchases[3]->score);
    }

    public function test_年ごとの平均取得単価を返す(): void
    {
        $result = $this->service($this->repository(self::SP500))->run(2024, 2024);

        $this->assertCount(1, $result->years);
        $this->assertSame(2024, $result->years[0]->year);
        $this->assertEqualsWithDelta(10_000.0, $result->years[0]->averageCosts['lump'], 1e-6);
        $this->assertEqualsWithDelta(
            1_200_000 / (50_000 / 10_000 + 50_000 / 11_000 + 50_000 / 12_000 + 350_000 / 9_000 + 700_000 / 12_000),
            $result->years[0]->averageCosts['timing'],
            1e-6,
        );
    }

    public function test_検証可能な年の範囲は初年の翌年から最後に12月がある年まで(): void
    {
        $this->assertSame([2024, 2024], $this->service($this->repository(self::SP500))->availableYears());
    }

    public function test_範囲外の年は検証可能な範囲に収める(): void
    {
        $result = $this->service($this->repository(self::SP500))->run(1990, 2030);

        $this->assertSame(2024, $result->fromYear);
        $this->assertSame(2024, $result->toYear);
    }

    public function test_データがなければnull(): void
    {
        $service = $this->service($this->repository([]));

        $this->assertNull($service->availableYears());
        $this->assertNull($service->run(2020, 2024));
    }

    /**
     * @param  array<string, float>  $sp500
     */
    private function repository(array $sp500): MarketPriceRepositoryInterface
    {
        $repository = $this->createMock(MarketPriceRepositoryInterface::class);
        $repository->method('getCloses')->willReturnCallback(fn (MarketSymbol $symbol) => match ($symbol) {
            MarketSymbol::Sp500 => $sp500,
            MarketSymbol::Vix => [],
            MarketSymbol::UsdJpy => array_map(fn () => 100.0, $sp500),
        });

        return $repository;
    }

    private function service(MarketPriceRepositoryInterface $repository): BacktestService
    {
        return new BacktestService(
            $repository,
            new MarketSnapshotBuilder(highWindowDays: 252, movingAverageDays: 200),
            new TimingScorer(
                scoring: ['drawdown' => [[-20, 4], [-10, 2]]],
                levels: [
                    ['key' => 'strong', 'label' => '強い買い場', 'min_score' => 4, 'multiplier' => 3.0],
                    ['key' => 'normal', 'label' => '通常', 'min_score' => 0, 'multiplier' => 0.5],
                ],
            ),
            new InvestmentPlanner(roundingUnit: 10_000),
            annualLimit: 1_200_000,
        );
    }
}
