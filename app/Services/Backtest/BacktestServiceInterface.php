<?php

declare(strict_types=1);

namespace App\Services\Backtest;

use App\Domain\Backtest\BacktestResult;

interface BacktestServiceInterface
{
    /**
     * 検証できる年の範囲
     *
     * @return array{0: int, 1: int}|null [開始年, 終了年]。データ不足なら null
     */
    public function availableYears(): ?array;

    /**
     * 成長投資枠の使い方3通り（一括・毎月均等・スコア連動）を比較する
     * 範囲外の年は検証できる範囲に収める
     */
    public function run(?int $fromYear, ?int $toYear): ?BacktestResult;
}
