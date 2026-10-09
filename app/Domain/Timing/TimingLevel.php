<?php

declare(strict_types=1);

namespace App\Domain\Timing;

final readonly class TimingLevel
{
    public function __construct(
        public string $key,
        public string $label,
        public int $minScore,
        public float $multiplier,
    ) {}
}
