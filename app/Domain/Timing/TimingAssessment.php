<?php

declare(strict_types=1);

namespace App\Domain\Timing;

use App\Domain\Quota\QuotaSummary;

final readonly class TimingAssessment
{
    /**
     * @param  list<ScoredSnapshot>  $history  古い順
     */
    public function __construct(
        public MarketSnapshot $snapshot,
        public TimingScore $score,
        public QuotaSummary $quota,
        public int $recommendedAmount,
        public array $history,
    ) {}
}
