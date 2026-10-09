<?php

declare(strict_types=1);

namespace App\Domain\Backtest;

final readonly class YearResult
{
    /**
     * @param  array<string, float>  $averageCosts  戦略キー => 平均取得単価（円）
     */
    public function __construct(
        public int $year,
        public array $averageCosts,
    ) {}
}
