<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domain\Backtest\BacktestResult;
use App\Domain\Backtest\StrategyResult;
use App\Domain\Backtest\TimingPurchase;
use App\Domain\Backtest\YearResult;
use App\Domain\Quota\QuotaSummary;
use App\Domain\Timing\MarketSnapshot;
use App\Domain\Timing\ScoredSnapshot;
use App\Domain\Timing\TimingAssessment;
use App\Domain\Timing\TimingLevel;
use App\Domain\Timing\TimingScore;

/**
 * Controller テスト用の Domain オブジェクト
 */
final class Fixtures
{
    public static function quota(int $usedThisYear = 900_000): QuotaSummary
    {
        return new QuotaSummary(
            year: 2026,
            annualLimit: 2_400_000,
            usedThisYear: $usedThisYear,
            remainingThisYear: 2_400_000 - $usedThisYear,
            lifetimeLimit: 12_000_000,
            usedLifetime: $usedThisYear,
            remainingLifetime: 12_000_000 - $usedThisYear,
            monthsLeft: 3,
            investable: 2_400_000 - $usedThisYear,
        );
    }

    public static function assessment(): TimingAssessment
    {
        $snapshot = new MarketSnapshot(
            date: '2026-10-09',
            sp500: 6630.0,
            sp500High: 7800.0,
            drawdownPct: -15.0,
            sp500Ma: 7000.0,
            maDeviationPct: -5.29,
            vix: 26.4,
            usdJpy: 148.2,
            usdJpyMa: 150.0,
            usdJpyDeviationPct: -1.2,
            sp500Jpy: 982_566.0,
        );
        $score = new TimingScore(
            total: 9,
            max: 11,
            breakdown: ['drawdown' => 3, 'ma_deviation' => 2, 'vix' => 2, 'usd_jpy_deviation' => 1],
            level: new TimingLevel('strong', '強い買い場', 6, 3.0),
        );

        return new TimingAssessment(
            snapshot: $snapshot,
            score: $score,
            quota: self::quota(),
            recommendedAmount: 1_500_000,
            history: [new ScoredSnapshot($snapshot, $score)],
        );
    }

    public static function backtest(): BacktestResult
    {
        return new BacktestResult(
            fromYear: 2010,
            toYear: 2020,
            valuationDate: '2020-12-31',
            valuationPrice: 389_000.0,
            strategies: [
                'lump' => new StrategyResult('lump', '年初に一括', 26_400_000, 200.0, 77_800_000.0, 132_000.0, 194.7),
                'monthly' => new StrategyResult('monthly', '毎月均等', 26_400_000, 195.0, 75_855_000.0, 135_384.6, 187.3),
                'timing' => new StrategyResult('timing', 'スコア連動', 26_400_000, 198.0, 77_022_000.0, 133_333.3, 191.7),
            ],
            years: [new YearResult(2010, ['lump' => 100_000.0, 'monthly' => 101_000.0, 'timing' => 99_500.0])],
            timingPurchases: [new TimingPurchase('2010-01-04', 100_000, 100_000.0, 0, 'normal')],
        );
    }
}
