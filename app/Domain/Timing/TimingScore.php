<?php

declare(strict_types=1);

namespace App\Domain\Timing;

final readonly class TimingScore
{
    /**
     * @param  array<string, int>  $breakdown  指標キー => 点数
     */
    public function __construct(
        public int $total,
        public int $max,
        public array $breakdown,
        public TimingLevel $level,
    ) {
    }
}
