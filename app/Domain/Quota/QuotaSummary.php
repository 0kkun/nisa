<?php

declare(strict_types=1);

namespace App\Domain\Quota;

final readonly class QuotaSummary
{
    /**
     * @param  int  $monthsLeft  今月を含む今年の残り月数
     * @param  int  $investable  今年まだ投資できる額（年間枠と生涯枠の残りの小さい方）
     */
    public function __construct(
        public int $year,
        public int $annualLimit,
        public int $usedThisYear,
        public int $remainingThisYear,
        public int $lifetimeLimit,
        public int $usedLifetime,
        public int $remainingLifetime,
        public int $monthsLeft,
        public int $investable,
    ) {}

    public function usedThisYearPct(): float
    {
        return $this->annualLimit > 0 ? min(100, $this->usedThisYear / $this->annualLimit * 100) : 0.0;
    }

    public function usedLifetimePct(): float
    {
        return $this->lifetimeLimit > 0 ? min(100, $this->usedLifetime / $this->lifetimeLimit * 100) : 0.0;
    }
}
