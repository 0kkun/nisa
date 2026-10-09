<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Enums\MarketSymbol;
use App\Models\MarketPrice;
use App\Repositories\MarketPriceRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketPriceRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private MarketPriceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new MarketPriceRepository;
    }

    public function test_終値を保存し件数を返す(): void
    {
        $count = $this->repository->upsertCloses(MarketSymbol::Sp500, [
            '2026-01-05' => 6000.5,
            '2026-01-06' => 6010.25,
        ]);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('market_prices', ['symbol' => '^GSPC', 'date' => '2026-01-05', 'close' => 6000.5]);
    }

    public function test_同じ日付は上書きする(): void
    {
        $this->repository->upsertCloses(MarketSymbol::Sp500, ['2026-01-05' => 6000.0]);
        $this->repository->upsertCloses(MarketSymbol::Sp500, ['2026-01-05' => 6100.0]);

        $this->assertSame(1, MarketPrice::count());
        $this->assertDatabaseHas('market_prices', ['date' => '2026-01-05', 'close' => 6100.0]);
    }

    public function test_空配列なら何もしない(): void
    {
        $this->assertSame(0, $this->repository->upsertCloses(MarketSymbol::Sp500, []));
    }

    public function test_最新日付を銘柄ごとに返す(): void
    {
        $this->repository->upsertCloses(MarketSymbol::Sp500, ['2026-01-05' => 1.0, '2026-01-07' => 1.0]);
        $this->repository->upsertCloses(MarketSymbol::Vix, ['2026-01-09' => 1.0]);

        $this->assertSame('2026-01-07', $this->repository->latestDate(MarketSymbol::Sp500));
        $this->assertNull($this->repository->latestDate(MarketSymbol::UsdJpy));
    }

    public function test_終値を日付昇順の連想配列で返す(): void
    {
        $this->repository->upsertCloses(MarketSymbol::Sp500, ['2026-01-07' => 3.0, '2026-01-05' => 1.0, '2026-01-06' => 2.0]);
        $this->repository->upsertCloses(MarketSymbol::Vix, ['2026-01-06' => 20.0]);

        $this->assertSame(
            ['2026-01-05' => 1.0, '2026-01-06' => 2.0, '2026-01-07' => 3.0],
            $this->repository->getCloses(MarketSymbol::Sp500),
        );
        $this->assertSame(
            ['2026-01-06' => 2.0, '2026-01-07' => 3.0],
            $this->repository->getCloses(MarketSymbol::Sp500, from: '2026-01-06'),
        );
    }
}
