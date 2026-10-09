<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Timing;

use App\Domain\Timing\MarketSnapshot;
use App\Domain\Timing\TimingScorer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TimingScorerTest extends TestCase
{
    private TimingScorer $scorer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->scorer = new TimingScorer(
            scoring: [
                'drawdown' => [[-20, 4], [-15, 3], [-10, 2], [-5, 1]],
                'ma_deviation' => [[-10, 3], [-5, 2], [0, 1]],
                'vix' => [[30, 3], [25, 2], [20, 1]],
                'usd_jpy_deviation' => [[0, 1]],
            ],
            levels: [
                ['key' => 'strong', 'label' => '強い買い場', 'min_score' => 6, 'multiplier' => 3.0],
                ['key' => 'buy', 'label' => '買い増し', 'min_score' => 3, 'multiplier' => 2.0],
                ['key' => 'normal', 'label' => '通常', 'min_score' => 0, 'multiplier' => 0.5],
            ],
        );
    }

    public static function drawdownProvider(): array
    {
        return [
            '高値更新中' => [0.0, 0],
            '-4.9%' => [-4.9, 0],
            '-5%ちょうど' => [-5.0, 1],
            '-12%' => [-12.0, 2],
            '-15%' => [-15.0, 3],
            '-35%' => [-35.0, 4],
        ];
    }

    #[DataProvider('drawdownProvider')]
    public function test_下落率の点数(float $drawdownPct, int $expected): void
    {
        $score = $this->scorer->score($this->snapshot(drawdownPct: $drawdownPct));

        $this->assertSame($expected, $score->breakdown['drawdown']);
    }

    public static function vixProvider(): array
    {
        return [
            '平常' => [15.0, 0],
            '20ちょうど' => [20.0, 1],
            '27' => [27.0, 2],
            'パニック' => [45.0, 3],
        ];
    }

    #[DataProvider('vixProvider')]
    public function test_vi_xの点数(float $vix, int $expected): void
    {
        $score = $this->scorer->score($this->snapshot(vix: $vix));

        $this->assertSame($expected, $score->breakdown['vix']);
    }

    public function test_移動平均乖離とドル円乖離の点数(): void
    {
        $score = $this->scorer->score($this->snapshot(maDeviationPct: -6.0, usdJpyDeviationPct: -0.1));

        $this->assertSame(2, $score->breakdown['ma_deviation']);
        $this->assertSame(1, $score->breakdown['usd_jpy_deviation']);
    }

    public function test_値がnullの指標は0点(): void
    {
        $score = $this->scorer->score($this->snapshot());

        $this->assertSame(
            ['drawdown' => 0, 'ma_deviation' => 0, 'vix' => 0, 'usd_jpy_deviation' => 0],
            $score->breakdown,
        );
        $this->assertSame(0, $score->total);
        $this->assertSame('normal', $score->level->key);
    }

    public function test_合計点でレベルが決まる(): void
    {
        // 2 + 2 = 4点 → 買い増し
        $buy = $this->scorer->score($this->snapshot(drawdownPct: -10.0, vix: 25.0));
        $this->assertSame(4, $buy->total);
        $this->assertSame('buy', $buy->level->key);
        $this->assertSame(2.0, $buy->level->multiplier);

        // 4 + 3 + 3 + 1 = 11点 → 強い買い場
        $strong = $this->scorer->score($this->snapshot(drawdownPct: -25.0, maDeviationPct: -15.0, vix: 40.0, usdJpyDeviationPct: -3.0));
        $this->assertSame(11, $strong->total);
        $this->assertSame('strong', $strong->level->key);
        $this->assertSame('強い買い場', $strong->level->label);
    }

    public function test_満点は各指標の最高点の合計(): void
    {
        $this->assertSame(11, $this->scorer->maxScore());
    }

    private function snapshot(
        float $drawdownPct = 0.0,
        ?float $maDeviationPct = null,
        ?float $vix = null,
        ?float $usdJpyDeviationPct = null,
    ): MarketSnapshot {
        return new MarketSnapshot(
            date: '2026-01-05',
            sp500: 100.0,
            sp500High: 100.0,
            drawdownPct: $drawdownPct,
            sp500Ma: null,
            maDeviationPct: $maDeviationPct,
            vix: $vix,
            usdJpy: null,
            usdJpyMa: null,
            usdJpyDeviationPct: $usdJpyDeviationPct,
            sp500Jpy: null,
        );
    }
}
