<?php

declare(strict_types=1);

namespace App\Domain\Timing;

/**
 * 市場指標を点数化し、買い場のレベルを判定する
 */
final class TimingScorer
{
    /** 値が閾値以上で加点する指標 */
    private const HIGHER_IS_BETTER = ['vix'];

    /** @var list<TimingLevel> min_score の高い順 */
    private array $levels;

    /**
     * @param  array<string, list<array{0: int|float, 1: int}>>  $scoring  指標キー => [閾値, 点数] の配列（点数の高い順）
     * @param  list<array{key: string, label: string, min_score: int, multiplier: float}>  $levels
     */
    public function __construct(
        private readonly array $scoring,
        array $levels,
    ) {
        $this->levels = array_map(
            fn (array $level) => new TimingLevel($level['key'], $level['label'], $level['min_score'], (float) $level['multiplier']),
            $levels,
        );
        usort($this->levels, fn (TimingLevel $a, TimingLevel $b) => $b->minScore <=> $a->minScore);
    }

    public function score(MarketSnapshot $snapshot): TimingScore
    {
        $values = [
            'drawdown' => $snapshot->drawdownPct,
            'ma_deviation' => $snapshot->maDeviationPct,
            'vix' => $snapshot->vix,
            'usd_jpy_deviation' => $snapshot->usdJpyDeviationPct,
        ];

        $breakdown = [];

        foreach ($values as $key => $value) {
            $breakdown[$key] = $this->pointsFor($key, $value);
        }

        $total = array_sum($breakdown);

        return new TimingScore($total, $this->maxScore(), $breakdown, $this->levelFor($total));
    }

    public function maxScore(): int
    {
        return array_sum(array_map(
            fn (array $rules) => max(array_column($rules, 1)),
            $this->scoring,
        ));
    }

    private function pointsFor(string $key, ?float $value): int
    {
        if ($value === null) {
            return 0;
        }

        $higherIsBetter = in_array($key, self::HIGHER_IS_BETTER, true);

        foreach ($this->scoring[$key] ?? [] as [$threshold, $points]) {
            if ($higherIsBetter ? $value >= $threshold : $value <= $threshold) {
                return $points;
            }
        }

        return 0;
    }

    private function levelFor(int $total): TimingLevel
    {
        foreach ($this->levels as $level) {
            if ($total >= $level->minScore) {
                return $level;
            }
        }

        return end($this->levels);
    }
}
