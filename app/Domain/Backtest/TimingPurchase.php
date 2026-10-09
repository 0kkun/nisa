<?php

declare(strict_types=1);

namespace App\Domain\Backtest;

final readonly class TimingPurchase
{
    public function __construct(
        public string $date,
        public int $amount,
        public float $price,
        public int $score,
        public string $levelKey,
    ) {}
}
