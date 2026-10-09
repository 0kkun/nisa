<?php

declare(strict_types=1);

namespace App\Domain\Backtest;

final readonly class StrategyResult
{
    public function __construct(
        public string $key,
        public string $label,
        public int $invested,
        public float $units,
        public float $finalValue,
        public float $averageCost,
        public float $returnPct,
    ) {}
}
