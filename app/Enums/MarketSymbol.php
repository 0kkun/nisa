<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * 取得対象の市場データ（値は Yahoo Finance のティッカー）
 */
enum MarketSymbol: string
{
    case Sp500 = '^GSPC';
    case Vix = '^VIX';
    case UsdJpy = 'JPY=X';

    public function label(): string
    {
        return match ($this) {
            self::Sp500 => 'S&P500',
            self::Vix => 'VIX',
            self::UsdJpy => 'ドル円',
        };
    }
}
