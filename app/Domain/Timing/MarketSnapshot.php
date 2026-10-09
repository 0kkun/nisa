<?php

declare(strict_types=1);

namespace App\Domain\Timing;

/**
 * ある営業日の市場指標（% の値はすべてパーセント表記）
 */
final readonly class MarketSnapshot
{
    public function __construct(
        public string $date,
        public float $sp500,
        public float $sp500High,
        public float $drawdownPct,
        public ?float $sp500Ma,
        public ?float $maDeviationPct,
        public ?float $vix,
        public ?float $usdJpy,
        public ?float $usdJpyMa,
        public ?float $usdJpyDeviationPct,
        public ?float $sp500Jpy,
    ) {}
}
