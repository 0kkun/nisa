<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Timing;

use App\Domain\Timing\MarketSnapshotBuilder;
use PHPUnit\Framework\TestCase;

class MarketSnapshotBuilderTest extends TestCase
{
    public function test_s_p500の営業日ごとにスナップショットを作る(): void
    {
        $snapshots = (new MarketSnapshotBuilder(highWindowDays: 3, movingAverageDays: 2))->build(
            sp500: ['2026-01-05' => 100.0, '2026-01-06' => 110.0, '2026-01-07' => 99.0],
            vix: [],
            usdJpy: [],
        );

        $this->assertSame(['2026-01-05', '2026-01-06', '2026-01-07'], array_keys($snapshots));
        $this->assertSame('2026-01-07', $snapshots['2026-01-07']->date);
        $this->assertSame(99.0, $snapshots['2026-01-07']->sp500);
    }

    public function test_高値からの下落率は直近の期間内の最高値で計算する(): void
    {
        $snapshots = (new MarketSnapshotBuilder(highWindowDays: 2, movingAverageDays: 2))->build(
            sp500: ['2026-01-05' => 200.0, '2026-01-06' => 100.0, '2026-01-07' => 90.0],
            vix: [],
            usdJpy: [],
        );

        $this->assertSame(200.0, $snapshots['2026-01-06']->sp500High);
        $this->assertEqualsWithDelta(-50.0, $snapshots['2026-01-06']->drawdownPct, 0.0001);
        // 期間2日なので 01-05 の 200 は窓から外れる
        $this->assertSame(100.0, $snapshots['2026-01-07']->sp500High);
        $this->assertEqualsWithDelta(-10.0, $snapshots['2026-01-07']->drawdownPct, 0.0001);
    }

    public function test_移動平均は期間分のデータが揃うまでnull(): void
    {
        $snapshots = (new MarketSnapshotBuilder(highWindowDays: 5, movingAverageDays: 2))->build(
            sp500: ['2026-01-05' => 100.0, '2026-01-06' => 120.0, '2026-01-07' => 99.0],
            vix: [],
            usdJpy: [],
        );

        $this->assertNull($snapshots['2026-01-05']->sp500Ma);
        $this->assertNull($snapshots['2026-01-05']->maDeviationPct);
        $this->assertEqualsWithDelta(110.0, $snapshots['2026-01-06']->sp500Ma, 0.0001);
        $this->assertEqualsWithDelta(109.5, $snapshots['2026-01-07']->sp500Ma, 0.0001);
        $this->assertEqualsWithDelta((99.0 / 109.5 - 1) * 100, $snapshots['2026-01-07']->maDeviationPct, 0.0001);
    }

    public function test_vi_xとドル円は当日以前の直近の値で補完する(): void
    {
        $snapshots = (new MarketSnapshotBuilder(highWindowDays: 5, movingAverageDays: 2))->build(
            sp500: ['2026-01-05' => 100.0, '2026-01-06' => 100.0, '2026-01-07' => 100.0],
            vix: ['2026-01-06' => 25.0],
            usdJpy: ['2026-01-04' => 150.0, '2026-01-05' => 140.0, '2026-01-07' => 160.0],
        );

        $this->assertNull($snapshots['2026-01-05']->vix);
        $this->assertSame(25.0, $snapshots['2026-01-06']->vix);
        $this->assertSame(25.0, $snapshots['2026-01-07']->vix);

        $this->assertSame(140.0, $snapshots['2026-01-05']->usdJpy);
        $this->assertSame(140.0, $snapshots['2026-01-06']->usdJpy);
        $this->assertSame(160.0, $snapshots['2026-01-07']->usdJpy);
    }

    public function test_ドル円の移動平均乖離はドル円自身の営業日で計算する(): void
    {
        $snapshots = (new MarketSnapshotBuilder(highWindowDays: 5, movingAverageDays: 2))->build(
            sp500: ['2026-01-07' => 100.0],
            vix: [],
            usdJpy: ['2026-01-05' => 140.0, '2026-01-06' => 160.0, '2026-01-07' => 135.0],
        );

        $this->assertEqualsWithDelta(147.5, $snapshots['2026-01-07']->usdJpyMa, 0.0001);
        $this->assertEqualsWithDelta((135.0 / 147.5 - 1) * 100, $snapshots['2026-01-07']->usdJpyDeviationPct, 0.0001);
    }

    public function test_円建て価格は_s_p500とドル円の積(): void
    {
        $snapshots = (new MarketSnapshotBuilder(highWindowDays: 5, movingAverageDays: 2))->build(
            sp500: ['2026-01-05' => 100.0, '2026-01-06' => 110.0],
            vix: [],
            usdJpy: ['2026-01-06' => 150.0],
        );

        $this->assertNull($snapshots['2026-01-05']->sp500Jpy);
        $this->assertEqualsWithDelta(16500.0, $snapshots['2026-01-06']->sp500Jpy, 0.0001);
    }

    public function test_データが空なら空配列(): void
    {
        $this->assertSame([], (new MarketSnapshotBuilder(252, 200))->build([], [], []));
    }
}
