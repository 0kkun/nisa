<?php

declare(strict_types=1);

namespace App\Services\MarketData;

use App\Enums\MarketSymbol;

interface MarketDataClientInterface
{
    /**
     * 日足の終値を取得
     *
     * @param  string|null  $from  Y-m-d。null なら全期間
     * @return array<string, float> 日付(Y-m-d) => 終値
     *
     * @throws \RuntimeException 取得に失敗した場合
     */
    public function fetchDailyCloses(MarketSymbol $symbol, ?string $from = null): array;
}
