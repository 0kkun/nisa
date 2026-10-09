<?php

declare(strict_types=1);

namespace App\Domain\Timing;

/**
 * 買い場レベルに応じた今月の投入額を決める
 */
final readonly class InvestmentPlanner
{
    public function __construct(
        private int $roundingUnit,
    ) {}

    /**
     * 「残り枠 ÷ 残り月数 × 倍率」を丸め単位で切り捨てる。最終月は残り全額。
     *
     * @param  int  $monthsLeft  今月を含む残り月数
     */
    public function monthlyAmount(int $remaining, int $monthsLeft, TimingLevel $level): int
    {
        if ($remaining <= 0) {
            return 0;
        }

        if ($monthsLeft <= 1) {
            return $remaining;
        }

        $amount = (int) (floor($remaining / $monthsLeft * $level->multiplier / $this->roundingUnit) * $this->roundingUnit);

        return min($remaining, $amount);
    }
}
