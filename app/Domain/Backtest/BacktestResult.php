<?php

declare(strict_types=1);

namespace App\Domain\Backtest;

final readonly class BacktestResult
{
    /**
     * @param  array<string, StrategyResult>  $strategies  lump / monthly / timing
     * @param  list<YearResult>  $years
     * @param  list<TimingPurchase>  $timingPurchases
     */
    public function __construct(
        public int $fromYear,
        public int $toYear,
        public string $valuationDate,
        public float $valuationPrice,
        public array $strategies,
        public array $years,
        public array $timingPurchases,
    ) {}
}
