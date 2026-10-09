<?php

declare(strict_types=1);

namespace App\Services\Backtest;

use App\Domain\Backtest\BacktestResult;
use App\Domain\Backtest\StrategyResult;
use App\Domain\Backtest\TimingPurchase;
use App\Domain\Backtest\YearResult;
use App\Domain\Timing\InvestmentPlanner;
use App\Domain\Timing\MarketSnapshot;
use App\Domain\Timing\MarketSnapshotBuilder;
use App\Domain\Timing\TimingScorer;
use App\Enums\MarketSymbol;
use App\Repositories\MarketPriceRepositoryInterface;

class BacktestService implements BacktestServiceInterface
{
    private const STRATEGIES = [
        'lump' => '年初に一括',
        'monthly' => '毎月均等',
        'timing' => 'スコア連動',
    ];

    /** @var array<string, MarketSnapshot>|null 月ごとの最初の営業日（Y-m => 指標） */
    private ?array $monthlySnapshots = null;

    /** @var array<string, MarketSnapshot> */
    private array $snapshots = [];

    public function __construct(
        private readonly MarketPriceRepositoryInterface $marketPriceRepository,
        private readonly MarketSnapshotBuilder $snapshotBuilder,
        private readonly TimingScorer $scorer,
        private readonly InvestmentPlanner $planner,
        private readonly int $annualLimit,
    ) {}

    public function availableYears(): ?array
    {
        $months = array_keys($this->monthlySnapshots());

        if ($months === []) {
            return null;
        }

        // 初年は指標の助走期間に充てる
        $first = (int) substr($months[0], 0, 4) + 1;
        $decembers = array_filter($months, fn (string $month) => str_ends_with($month, '-12'));
        $last = $decembers === [] ? null : (int) substr(end($decembers), 0, 4);

        return ($last === null || $first > $last) ? null : [$first, $last];
    }

    public function run(?int $fromYear, ?int $toYear): ?BacktestResult
    {
        $range = $this->availableYears();

        if ($range === null) {
            return null;
        }

        $to = min(max($toYear ?? $range[1], $range[0]), $range[1]);
        $from = min(max($fromYear ?? $range[0], $range[0]), $to);

        $invested = array_fill_keys(array_keys(self::STRATEGIES), 0);
        $units = array_fill_keys(array_keys(self::STRATEGIES), 0.0);
        $years = [];
        $timingPurchases = [];

        for ($year = $from; $year <= $to; $year++) {
            $months = array_values(array_filter(
                $this->monthlySnapshots(),
                fn (string $month) => str_starts_with($month, "{$year}-"),
                ARRAY_FILTER_USE_KEY,
            ));
            $yearInvested = array_fill_keys(array_keys(self::STRATEGIES), 0);
            $yearUnits = array_fill_keys(array_keys(self::STRATEGIES), 0.0);
            $remaining = $this->annualLimit;
            $count = count($months);

            foreach ($months as $i => $snapshot) {
                $amounts = [
                    'lump' => $i === 0 ? $this->annualLimit : 0,
                    'monthly' => $i === $count - 1
                        ? $this->annualLimit - intdiv($this->annualLimit, $count) * ($count - 1)
                        : intdiv($this->annualLimit, $count),
                ];

                $score = $this->scorer->score($snapshot);
                $amounts['timing'] = $this->planner->monthlyAmount($remaining, $count - $i, $score->level);
                $remaining -= $amounts['timing'];
                $timingPurchases[] = new TimingPurchase($snapshot->date, $amounts['timing'], $snapshot->sp500Jpy, $score->total, $score->level->key);

                foreach ($amounts as $key => $amount) {
                    $yearInvested[$key] += $amount;
                    $yearUnits[$key] += $amount / $snapshot->sp500Jpy;
                }
            }

            foreach (array_keys(self::STRATEGIES) as $key) {
                $invested[$key] += $yearInvested[$key];
                $units[$key] += $yearUnits[$key];
            }

            $years[] = new YearResult($year, array_map(
                fn (string $key) => $yearUnits[$key] > 0 ? $yearInvested[$key] / $yearUnits[$key] : 0.0,
                array_combine(array_keys(self::STRATEGIES), array_keys(self::STRATEGIES)),
            ));
        }

        $valuation = $this->valuationSnapshot($to);
        $strategies = [];

        foreach (self::STRATEGIES as $key => $label) {
            $finalValue = $units[$key] * $valuation->sp500Jpy;
            $strategies[$key] = new StrategyResult(
                key: $key,
                label: $label,
                invested: $invested[$key],
                units: $units[$key],
                finalValue: $finalValue,
                averageCost: $units[$key] > 0 ? $invested[$key] / $units[$key] : 0.0,
                returnPct: $invested[$key] > 0 ? ($finalValue / $invested[$key] - 1) * 100 : 0.0,
            );
        }

        return new BacktestResult($from, $to, $valuation->date, $valuation->sp500Jpy, $strategies, $years, $timingPurchases);
    }

    /**
     * @return array<string, MarketSnapshot>
     */
    private function monthlySnapshots(): array
    {
        if ($this->monthlySnapshots !== null) {
            return $this->monthlySnapshots;
        }

        $this->snapshots = array_filter(
            $this->snapshotBuilder->build(
                $this->marketPriceRepository->getCloses(MarketSymbol::Sp500),
                $this->marketPriceRepository->getCloses(MarketSymbol::Vix),
                $this->marketPriceRepository->getCloses(MarketSymbol::UsdJpy),
            ),
            fn (MarketSnapshot $snapshot) => $snapshot->sp500Jpy !== null,
        );

        $this->monthlySnapshots = [];

        foreach ($this->snapshots as $date => $snapshot) {
            $this->monthlySnapshots[substr($date, 0, 7)] ??= $snapshot;
        }

        return $this->monthlySnapshots;
    }

    private function valuationSnapshot(int $toYear): MarketSnapshot
    {
        $valuation = null;

        foreach ($this->snapshots as $date => $snapshot) {
            if ($date > "{$toYear}-12-31") {
                break;
            }

            $valuation = $snapshot;
        }

        return $valuation;
    }
}
