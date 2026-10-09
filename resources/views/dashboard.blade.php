@extends('layouts.app')

@section('title', 'ダッシュボード')

@section('content')
    @if ($assessment === null)
        <x-empty-data />
    @else
        @php
            $snapshot = $assessment->snapshot;
            $score = $assessment->score;
            $quota = $assessment->quota;
            $pct = fn (?float $value) => $value === null ? '—' : sprintf('%+.1f%%', $value);
            $maxPoints = fn (string $key) => max(array_column(config("nisa.scoring.{$key}"), 1));
            $indicators = [
                [
                    'key' => 'drawdown',
                    'label' => '52週高値からの下落率',
                    'value' => $pct($snapshot->drawdownPct),
                    'detail' => '高値 '.number_format($snapshot->sp500High, 2).' / 現在 '.number_format($snapshot->sp500, 2),
                ],
                [
                    'key' => 'ma_deviation',
                    'label' => '200日線からの乖離',
                    'value' => $pct($snapshot->maDeviationPct),
                    'detail' => $snapshot->sp500Ma === null ? 'データ不足' : '200日線 '.number_format($snapshot->sp500Ma, 2),
                ],
                [
                    'key' => 'vix',
                    'label' => 'VIX（恐怖指数）',
                    'value' => $snapshot->vix === null ? '—' : number_format($snapshot->vix, 1),
                    'detail' => '20超で警戒、30超はパニック水準',
                ],
                [
                    'key' => 'usd_jpy_deviation',
                    'label' => 'ドル円（200日線比）',
                    'value' => $pct($snapshot->usdJpyDeviationPct),
                    'detail' => $snapshot->usdJpy === null ? 'データ不足' : number_format($snapshot->usdJpy, 2).' 円。マイナスは円高',
                ],
            ];
            $chartData = [
                'dates' => array_map(fn ($p) => $p->snapshot->date, $assessment->history),
                'sp500' => array_map(fn ($p) => round($p->snapshot->sp500, 2), $assessment->history),
                'ma' => array_map(fn ($p) => $p->snapshot->sp500Ma === null ? null : round($p->snapshot->sp500Ma, 2), $assessment->history),
                'scores' => array_map(fn ($p) => $p->score->total, $assessment->history),
                'levels' => array_map(fn ($p) => $p->score->level->key, $assessment->history),
                'maxScore' => $score->max,
            ];
        @endphp

        <section class="rounded-lg border border-line bg-card p-6 grid gap-6 md:grid-cols-[1fr_auto] md:items-end">
            <div class="flex flex-col gap-3">
                <div class="flex flex-wrap items-center gap-3">
                    <x-level-badge :level="$score->level" />
                    <span class="text-sm text-muted num">スコア {{ $score->total }} / {{ $score->max }}・{{ $snapshot->date }} 終値時点</span>
                </div>
                <div>
                    <p class="text-sm text-muted">{{ now()->month }}月の推奨投入額（成長投資枠）</p>
                    <p class="text-4xl font-semibold tracking-tight num">¥{{ number_format($assessment->recommendedAmount) }}</p>
                </div>
                <p class="text-sm text-muted num">
                    投資可能額 ¥{{ number_format($quota->investable) }} ÷ 残り{{ $quota->monthsLeft }}か月 × {{ $score->level->multiplier }}倍
                    @if ($quota->monthsLeft === 1)
                        ・最終月なので残り全額
                    @endif
                </p>
            </div>
            <a href="{{ route('purchases.index') }}" class="text-sm text-accent hover:underline justify-self-start md:justify-self-end">購入を記録する →</a>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($indicators as $indicator)
                @php($points = $score->breakdown[$indicator['key']])
                <div class="rounded-lg border border-line bg-card p-4 flex flex-col gap-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs text-muted">{{ $indicator['label'] }}</p>
                        <p class="text-xs num {{ $points > 0 ? 'text-accent font-semibold' : 'text-muted' }}">{{ $points }} / {{ $maxPoints($indicator['key']) }}点</p>
                    </div>
                    <p class="text-2xl font-semibold num">{{ $indicator['value'] }}</p>
                    <p class="text-xs text-muted num">{{ $indicator['detail'] }}</p>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-line bg-card p-4 flex flex-col gap-3">
            <h2 class="text-sm font-semibold">S&P500 と買い場スコアの推移</h2>
            <div class="h-80"><canvas id="market-chart"></canvas></div>
            <script type="application/json" id="market-data">@json($chartData)</script>
        </section>

        <section class="rounded-lg border border-line bg-card p-4 flex flex-col gap-3">
            <div class="flex items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold">{{ $quota->year }}年の成長投資枠</h2>
                <p class="text-sm num">¥{{ number_format($quota->usedThisYear) }} / ¥{{ number_format($quota->annualLimit) }}</p>
            </div>
            <div class="h-2 rounded-full bg-paper overflow-hidden">
                <div class="h-full bg-accent" style="width: {{ $quota->usedThisYearPct() }}%"></div>
            </div>
            <p class="text-xs text-muted num">残り ¥{{ number_format($quota->remainingThisYear) }}・生涯枠の残り ¥{{ number_format($quota->remainingLifetime) }}</p>
        </section>
    @endif
@endsection
