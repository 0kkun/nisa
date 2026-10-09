@extends('layouts.app')

@section('title', 'バックテスト')

@section('content')
    @if ($result === null)
        <x-empty-data />
    @else
        @php
            $best = collect($result->strategies)->sortByDesc('finalValue')->keys()->first();
            $years = range($availableYears[0], $availableYears[1]);
            $timingData = array_map(fn ($p) => [
                'date' => $p->date,
                'amount' => $p->amount,
                'score' => $p->score,
                'level' => $p->levelKey,
            ], $result->timingPurchases);
        @endphp

        <section class="flex flex-col gap-3">
            <form method="GET" action="{{ route('backtest.index') }}" class="flex flex-wrap items-end gap-3">
                <label class="flex flex-col gap-1 text-xs text-muted">
                    開始年
                    <select id="from_year" name="from_year" class="rounded-md border border-line bg-card px-3 py-2 text-sm text-ink num">
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($year === $result->fromYear)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="flex flex-col gap-1 text-xs text-muted">
                    終了年
                    <select id="to_year" name="to_year" class="rounded-md border border-line bg-card px-3 py-2 text-sm text-ink num">
                        @foreach ($years as $year)
                            <option value="{{ $year }}" @selected($year === $result->toYear)>{{ $year }}</option>
                        @endforeach
                    </select>
                </label>
                <button type="submit" class="rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-accent cursor-pointer">検証する</button>
            </form>
            <p class="text-xs text-muted num">
                毎年 ¥{{ number_format(config('nisa.growth_annual_limit')) }} を円建てS&P500（配当なしの価格指数 × ドル円）に投資し、{{ $result->valuationDate }} の価格 ¥{{ number_format($result->valuationPrice) }} で評価。売買は各月の最初の営業日の終値。
            </p>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            @foreach ($result->strategies as $key => $strategy)
                <div @class([
                    'rounded-lg border bg-card p-4 flex flex-col gap-3',
                    'border-accent ring-1 ring-accent' => $key === $best,
                    'border-line' => $key !== $best,
                ])>
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold">{{ $strategy->label }}</p>
                        @if ($key === $best)
                            <span class="text-xs text-accent font-semibold">最も高い</span>
                        @endif
                    </div>
                    <p class="num"><span class="text-2xl font-semibold">¥{{ number_format($strategy->finalValue) }}</span></p>
                    <dl class="grid grid-cols-2 gap-y-1 text-xs num">
                        <dt class="text-muted">投資額</dt><dd class="text-right">¥{{ number_format($strategy->invested) }}</dd>
                        <dt class="text-muted">リターン</dt><dd class="text-right">{{ sprintf('%+.1f%%', $strategy->returnPct) }}</dd>
                        <dt class="text-muted">平均取得単価</dt><dd class="text-right">¥{{ number_format($strategy->averageCost) }}</dd>
                    </dl>
                </div>
            @endforeach
        </section>

        <section class="rounded-lg border border-line bg-card p-4 flex flex-col gap-3">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-sm font-semibold">スコア連動の毎月の投入額</h2>
                <p class="flex gap-3 text-xs text-muted">
                    <span><span class="inline-block size-2 rounded-full bg-level-normal"></span> 通常</span>
                    <span><span class="inline-block size-2 rounded-full bg-level-buy"></span> 買い増し</span>
                    <span><span class="inline-block size-2 rounded-full bg-level-strong"></span> 強い買い場</span>
                </p>
            </div>
            <div class="h-64"><canvas id="timing-chart"></canvas></div>
            <script type="application/json" id="timing-data">@json($timingData)</script>
        </section>

        <section class="rounded-lg border border-line bg-card overflow-x-auto">
            <table class="w-full text-sm">
                <caption class="text-left text-sm font-semibold px-4 pt-4 pb-2">年ごとの平均取得単価（低いほど安く買えた）</caption>
                <thead class="text-xs text-muted border-b border-line">
                    <tr>
                        <th class="text-left font-normal px-4 py-2">年</th>
                        @foreach ($result->strategies as $strategy)
                            <th class="text-right font-normal px-4 py-2">{{ $strategy->label }}</th>
                        @endforeach
                        <th class="text-right font-normal px-4 py-2">スコア連動 vs 毎月均等</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($result->years as $row)
                        @php($cheapest = array_keys($row->averageCosts, min($row->averageCosts))[0])
                        @php($diff = ($row->averageCosts['timing'] / $row->averageCosts['monthly'] - 1) * 100)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-2 num">{{ $row->year }}</td>
                            @foreach ($row->averageCosts as $key => $cost)
                                <td class="px-4 py-2 num text-right {{ $key === $cheapest ? 'text-accent font-semibold' : '' }}">¥{{ number_format($cost) }}</td>
                            @endforeach
                            <td class="px-4 py-2 num text-right {{ $diff < 0 ? 'text-accent' : 'text-muted' }}">{{ sprintf('%+.1f%%', $diff) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
