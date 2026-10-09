<?php

declare(strict_types=1);

namespace App\Domain\Timing;

/**
 * 日足の終値から、S&P500 の営業日ごとの指標を計算する
 */
final readonly class MarketSnapshotBuilder
{
    public function __construct(
        private int $highWindowDays,
        private int $movingAverageDays,
    ) {}

    /**
     * @param  array<string, float>  $sp500  日付 => 終値（日付昇順）
     * @param  array<string, float>  $vix
     * @param  array<string, float>  $usdJpy
     * @return array<string, MarketSnapshot> 日付 => 指標（日付昇順）
     */
    public function build(array $sp500, array $vix, array $usdJpy): array
    {
        ksort($sp500);
        ksort($vix);
        ksort($usdJpy);

        $usdJpyMas = $this->movingAverages($usdJpy);

        $vixCursor = new ForwardFill($vix);
        $usdJpyCursor = new ForwardFill($usdJpy);
        $usdJpyMaCursor = new ForwardFill($usdJpyMas);

        $sp500Mas = $this->movingAverages($sp500);
        $closes = array_values($sp500);
        $snapshots = [];

        foreach (array_keys($sp500) as $i => $date) {
            $close = $closes[$i];
            $high = max(array_slice($closes, max(0, $i - $this->highWindowDays + 1), min($i + 1, $this->highWindowDays)));
            $ma = $sp500Mas[$date] ?? null;
            $usdJpyValue = $usdJpyCursor->at($date);
            $usdJpyMa = $usdJpyMaCursor->at($date);

            $snapshots[$date] = new MarketSnapshot(
                date: $date,
                sp500: $close,
                sp500High: $high,
                drawdownPct: ($close / $high - 1) * 100,
                sp500Ma: $ma,
                maDeviationPct: $ma === null ? null : ($close / $ma - 1) * 100,
                vix: $vixCursor->at($date),
                usdJpy: $usdJpyValue,
                usdJpyMa: $usdJpyMa,
                usdJpyDeviationPct: ($usdJpyValue === null || $usdJpyMa === null) ? null : ($usdJpyValue / $usdJpyMa - 1) * 100,
                sp500Jpy: $usdJpyValue === null ? null : $close * $usdJpyValue,
            );
        }

        return $snapshots;
    }

    /**
     * 単純移動平均（期間分のデータが揃った日のみ）
     *
     * @param  array<string, float>  $closes
     * @return array<string, float>
     */
    private function movingAverages(array $closes): array
    {
        $result = [];
        $values = array_values($closes);
        $sum = 0.0;

        foreach (array_keys($closes) as $i => $date) {
            $sum += $values[$i];

            if ($i >= $this->movingAverageDays) {
                $sum -= $values[$i - $this->movingAverageDays];
            }

            if ($i >= $this->movingAverageDays - 1) {
                $result[$date] = $sum / $this->movingAverageDays;
            }
        }

        return $result;
    }
}
