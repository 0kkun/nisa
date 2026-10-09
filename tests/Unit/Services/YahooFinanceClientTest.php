<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MarketSymbol;
use App\Services\MarketData\YahooFinanceClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class YahooFinanceClientTest extends TestCase
{
    public function test_日足の終値を取引所の現地日付で返す(): void
    {
        Http::fake([
            'query1.finance.yahoo.com/*' => Http::response($this->chartResponse(
                timezone: 'America/New_York',
                // 2026-10-05 09:30 EDT, 2026-10-06 09:30 EDT, 2026-10-07 09:30 EDT
                timestamps: [1791207000, 1791293400, 1791379800],
                closes: [7773.95, null, 7801.77],
            )),
        ]);

        $closes = (new YahooFinanceClient)->fetchDailyCloses(MarketSymbol::Sp500);

        // null の日は除外する
        $this->assertSame(['2026-10-05' => 7773.95, '2026-10-07' => 7801.77], $closes);
    }

    public function test_開始日を指定しない場合は1970年から日足で取得する(): void
    {
        Http::fake(['*' => Http::response($this->chartResponse('UTC', [], []))]);

        (new YahooFinanceClient)->fetchDailyCloses(MarketSymbol::Vix);

        // range=max は3か月足に間引かれるため使わない
        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/v8/finance/chart/%5EVIX')
                && (int) $request['period1'] === 0
                && ! isset($request['range'])
                && $request['interval'] === '1d';
        });
    }

    public function test_開始日を指定した場合はその日以降を取得する(): void
    {
        Http::fake(['*' => Http::response($this->chartResponse('UTC', [], []))]);

        (new YahooFinanceClient)->fetchDailyCloses(MarketSymbol::UsdJpy, from: '2026-10-01');

        Http::assertSent(function (Request $request) {
            return str_contains($request->url(), '/v8/finance/chart/JPY%3DX')
                && (int) $request['period1'] === strtotime('2026-10-01 00:00:00 UTC')
                && ! isset($request['range']);
        });
    }

    public function test_詳細なブラウザ_u_aだと429になるため簡素な_u_aで送る(): void
    {
        Http::fake(['*' => Http::response($this->chartResponse('UTC', [], []))]);

        (new YahooFinanceClient)->fetchDailyCloses(MarketSymbol::Sp500);

        Http::assertSent(fn (Request $request) => $request->header('User-Agent') === ['Mozilla/5.0']);
    }

    public function test_エラー応答なら例外(): void
    {
        Http::fake(['*' => Http::response(['chart' => ['result' => null, 'error' => ['code' => 'Not Found']]], 404)]);

        $this->expectException(RuntimeException::class);

        (new YahooFinanceClient)->fetchDailyCloses(MarketSymbol::Sp500);
    }

    /**
     * @param  list<int>  $timestamps
     * @param  list<float|null>  $closes
     */
    private function chartResponse(string $timezone, array $timestamps, array $closes): array
    {
        return [
            'chart' => [
                'result' => [[
                    'meta' => ['exchangeTimezoneName' => $timezone],
                    'timestamp' => $timestamps,
                    'indicators' => ['quote' => [['close' => $closes]]],
                ]],
                'error' => null,
            ],
        ];
    }
}
