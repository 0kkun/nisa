<?php

declare(strict_types=1);

namespace App\Domain\Timing;

final readonly class ScoredSnapshot
{
    public function __construct(
        public MarketSnapshot $snapshot,
        public TimingScore $score,
    ) {}
}
