<?php

declare(strict_types=1);

namespace App\Services\Timing;

use App\Domain\Timing\InvestmentPlanner;
use App\Domain\Timing\MarketSnapshotBuilder;
use App\Domain\Timing\ScoredSnapshot;
use App\Domain\Timing\TimingAssessment;
use App\Domain\Timing\TimingScorer;
use App\Enums\MarketSymbol;
use App\Repositories\MarketPriceRepositoryInterface;
use App\Services\Quota\GrowthQuotaServiceInterface;
use Carbon\CarbonImmutable;

class TimingService implements TimingServiceInterface
{
    /** 指標計算の助走期間（暦日）。252営業日の高値と200日線をまかなう */
    private const WARMUP_DAYS = 400;

    public function __construct(
        private readonly MarketPriceRepositoryInterface $marketPriceRepository,
        private readonly GrowthQuotaServiceInterface $growthQuotaService,
        private readonly MarketSnapshotBuilder $snapshotBuilder,
        private readonly TimingScorer $scorer,
        private readonly InvestmentPlanner $planner,
        private readonly int $historyDays,
    ) {}

    public function assess(CarbonImmutable $today): ?TimingAssessment
    {
        $from = $today->subDays((int) ceil($this->historyDays * 1.5) + self::WARMUP_DAYS)->format('Y-m-d');

        $snapshots = $this->snapshotBuilder->build(
            $this->marketPriceRepository->getCloses(MarketSymbol::Sp500, $from),
            $this->marketPriceRepository->getCloses(MarketSymbol::Vix, $from),
            $this->marketPriceRepository->getCloses(MarketSymbol::UsdJpy, $from),
        );

        if ($snapshots === []) {
            return null;
        }

        $history = array_map(
            fn ($snapshot) => new ScoredSnapshot($snapshot, $this->scorer->score($snapshot)),
            array_values(array_slice($snapshots, -$this->historyDays)),
        );
        $latest = end($history);
        $quota = $this->growthQuotaService->summary($today);

        return new TimingAssessment(
            snapshot: $latest->snapshot,
            score: $latest->score,
            quota: $quota,
            recommendedAmount: $this->planner->monthlyAmount($quota->investable, $quota->monthsLeft, $latest->score->level),
            history: $history,
        );
    }
}
