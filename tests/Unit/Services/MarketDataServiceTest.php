<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\MarketSymbol;
use App\Repositories\MarketPriceRepositoryInterface;
use App\Services\MarketData\MarketDataClientInterface;
use App\Services\MarketData\MarketDataService;
use Tests\TestCase;

class MarketDataServiceTest extends TestCase
{
    public function test_未取得の銘柄は全期間_取得済みの銘柄は直近7日前から取得して保存する(): void
    {
        $repository = $this->createMock(MarketPriceRepositoryInterface::class);
        $repository->method('latestDate')->willReturnMap([
            [MarketSymbol::Sp500, '2026-10-09'],
            [MarketSymbol::Vix, null],
            [MarketSymbol::UsdJpy, '2026-10-01'],
        ]);

        $client = $this->createMock(MarketDataClientInterface::class);
        $client->expects($this->exactly(3))
            ->method('fetchDailyCloses')
            ->willReturnCallback(fn (MarketSymbol $symbol, ?string $from) => match ($symbol) {
                MarketSymbol::Sp500 => $from === '2026-10-02' ? ['2026-10-09' => 1.0] : $this->fail("unexpected from: {$from}"),
                MarketSymbol::Vix => $from === null ? ['2026-10-08' => 2.0, '2026-10-09' => 3.0] : $this->fail('unexpected from'),
                MarketSymbol::UsdJpy => $from === '2026-09-24' ? [] : $this->fail("unexpected from: {$from}"),
            });

        $repository->expects($this->exactly(3))
            ->method('upsertCloses')
            ->willReturnCallback(fn (MarketSymbol $symbol, array $closes) => count($closes));

        $result = (new MarketDataService($client, $repository))->sync();

        $this->assertSame(['^GSPC' => 1, '^VIX' => 2, 'JPY=X' => 0], $result);
    }
}
