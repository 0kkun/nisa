@extends('layouts.app')

@section('title', '枠管理')

@section('content')
    <section class="grid gap-4 md:grid-cols-2">
        @foreach ([
            ['label' => $quota->year.'年の成長投資枠', 'used' => $quota->usedThisYear, 'limit' => $quota->annualLimit, 'remaining' => $quota->remainingThisYear, 'pct' => $quota->usedThisYearPct()],
            ['label' => '生涯の成長投資枠', 'used' => $quota->usedLifetime, 'limit' => $quota->lifetimeLimit, 'remaining' => $quota->remainingLifetime, 'pct' => $quota->usedLifetimePct()],
        ] as $card)
            <div class="rounded-lg border border-line bg-card p-4 flex flex-col gap-3">
                <p class="text-xs text-muted">{{ $card['label'] }}</p>
                <p class="num"><span class="text-xs text-muted">残り</span> <span class="text-2xl font-semibold">¥{{ number_format($card['remaining']) }}</span></p>
                <div class="h-2 rounded-full bg-paper overflow-hidden">
                    <div class="h-full bg-accent" style="width: {{ $card['pct'] }}%"></div>
                </div>
                <p class="text-xs text-muted num">利用 ¥{{ number_format($card['used']) }} / ¥{{ number_format($card['limit']) }}</p>
            </div>
        @endforeach
    </section>

    <p class="text-sm text-muted num">
        今年の残り{{ $quota->monthsLeft }}か月で使い切るなら、月あたり ¥{{ number_format(intdiv($quota->investable, max(1, $quota->monthsLeft))) }} のペースです。
    </p>

    <section class="rounded-lg border border-line bg-card p-4 flex flex-col gap-4">
        <h2 class="text-sm font-semibold">購入を記録</h2>
        <form method="POST" action="{{ route('purchases.store') }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-[auto_auto_1fr_1fr_auto] lg:items-end">
            @csrf
            <label class="flex flex-col gap-1 text-xs text-muted">
                購入日
                <input type="date" id="purchased_on" name="purchased_on" value="{{ old('purchased_on', now()->format('Y-m-d')) }}" required
                       class="rounded-md border border-line bg-card px-3 py-2 text-sm text-ink num">
            </label>
            <label class="flex flex-col gap-1 text-xs text-muted">
                金額（円）
                <input type="number" id="amount" name="amount" value="{{ old('amount') }}" min="1" step="1" required placeholder="300000"
                       class="rounded-md border border-line bg-card px-3 py-2 text-sm text-ink num">
            </label>
            <label class="flex flex-col gap-1 text-xs text-muted">
                ファンド名
                <input type="text" id="fund_name" name="fund_name" value="{{ old('fund_name', 'eMAXIS Slim 米国株式(S&P500)') }}" required maxlength="100"
                       class="rounded-md border border-line bg-card px-3 py-2 text-sm text-ink">
            </label>
            <label class="flex flex-col gap-1 text-xs text-muted">
                メモ
                <input type="text" id="note" name="note" value="{{ old('note') }}" maxlength="255" placeholder="例: VIX 30超えで買い増し"
                       class="rounded-md border border-line bg-card px-3 py-2 text-sm text-ink">
            </label>
            <button type="submit" class="rounded-md bg-ink px-4 py-2 text-sm font-semibold text-white hover:bg-accent cursor-pointer">記録する</button>
        </form>
        @if ($errors->any())
            <ul class="text-sm text-red-700 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="rounded-lg border border-line bg-card overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="text-xs text-muted border-b border-line">
                <tr>
                    <th class="text-left font-normal px-4 py-2">購入日</th>
                    <th class="text-right font-normal px-4 py-2">金額</th>
                    <th class="text-left font-normal px-4 py-2">ファンド</th>
                    <th class="text-left font-normal px-4 py-2">メモ</th>
                    <th class="px-4 py-2"></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($purchases as $purchase)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-2 num whitespace-nowrap">{{ $purchase->purchased_on->format('Y-m-d') }}</td>
                        <td class="px-4 py-2 num text-right whitespace-nowrap">¥{{ number_format($purchase->amount) }}</td>
                        <td class="px-4 py-2">{{ $purchase->fund_name }}</td>
                        <td class="px-4 py-2 text-muted">{{ $purchase->note }}</td>
                        <td class="px-4 py-2 text-right">
                            <form method="POST" action="{{ route('purchases.destroy', $purchase->id) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-muted hover:text-red-700 cursor-pointer">削除</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-6 text-center text-muted">まだ購入の記録がありません。上のフォームから成長投資枠で買った分を記録してください。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
@endsection
