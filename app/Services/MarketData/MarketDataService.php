<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Enums\MarketSymbol;
use App\Repositories\MarketPriceRepositoryInterface;
use Carbon\CarbonImmutable;

class MarketDataService implements MarketDataServiceInterface
{
    /** 取得済みの銘柄も、終値の訂正を拾うため直近この日数は取り直す */
    private const REFETCH_DAYS = 7;

    public function __construct(
        private readonly MarketDataClientInterface $client,
        private readonly MarketPriceRepositoryInterface $marketPriceRepository,
    ) {}

    public function sync(): array
    {
        $result = [];

        foreach (MarketSymbol::cases() as $symbol) {
            $latest = $this->marketPriceRepository->latestDate($symbol);
            $from = $latest === null ? null : CarbonImmutable::parse($latest)->subDays(self::REFETCH_DAYS)->format('Y-m-d');

            $closes = $this->client->fetchDailyCloses($symbol, $from);
            $result[$symbol->value] = $this->marketPriceRepository->upsertCloses($symbol, $closes);
        }

        return $result;
    }
}
