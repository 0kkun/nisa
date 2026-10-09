<?php

declare(strict_types=1);

namespace App\Services\MarketData;

interface MarketDataServiceInterface
{
    /**
     * 全銘柄の日足を取得して保存する
     *
     * @return array<string, int> ティッカー => 保存件数
     */
    public function sync(): array;
}
