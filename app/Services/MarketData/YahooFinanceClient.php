<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Enums\MarketSymbol;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YahooFinanceClient implements MarketDataClientInterface
{
    private const BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart/';

    // 詳細なブラウザ UA は Cookie なしだと 429 で弾かれる
    private const USER_AGENT = 'Mozilla/5.0';

    public function fetchDailyCloses(MarketSymbol $symbol, ?string $from = null): array
    {
        // range=max は3か月足に間引かれるため、全期間でも period1 で指定する
        $query = [
            'interval' => '1d',
            'events' => 'history',
            'period1' => $from === null ? 0 : CarbonImmutable::parse($from, 'UTC')->startOfDay()->getTimestamp(),
            'period2' => CarbonImmutable::now('UTC')->addDay()->startOfDay()->getTimestamp(),
        ];

        $response = Http::withUserAgent(self::USER_AGENT)
            ->timeout(30)
            ->retry(2, 1000, throw: false)
            ->get(self::BASE_URL.rawurlencode($symbol->value), $query);

        $result = $response->json('chart.result.0');

        if ($response->failed() || ! is_array($result)) {
            throw new RuntimeException("{$symbol->label()} の取得に失敗しました (HTTP {$response->status()})");
        }

        $timezone = $result['meta']['exchangeTimezoneName'] ?? 'UTC';
        $timestamps = $result['timestamp'] ?? [];
        $closes = $result['indicators']['quote'][0]['close'] ?? [];
        $data = [];

        foreach ($timestamps as $i => $timestamp) {
            if (! isset($closes[$i])) {
                continue;
            }

            $date = CarbonImmutable::createFromTimestamp($timestamp, $timezone)->format('Y-m-d');
            $data[$date] = (float) $closes[$i];
        }

        ksort($data);

        return $data;
    }
}
