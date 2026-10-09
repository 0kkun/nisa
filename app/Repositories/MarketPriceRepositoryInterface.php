<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\MarketSymbol;

interface MarketPriceRepositoryInterface
{
    /**
     * 終値を保存（同じ銘柄・日付は上書き）
     *
     * @param  array<string, float>  $closes  日付(Y-m-d) => 終値
     * @return int 保存した件数
     */
    public function upsertCloses(MarketSymbol $symbol, array $closes): int;

    /**
     * 保存済みの最新日付（Y-m-d）
     */
    public function latestDate(MarketSymbol $symbol): ?string;

    /**
     * 終値を日付昇順で取得
     *
     * @return array<string, float> 日付(Y-m-d) => 終値
     */
    public function getCloses(MarketSymbol $symbol, ?string $from = null): array;
}
