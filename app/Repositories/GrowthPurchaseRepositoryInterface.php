<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GrowthPurchase;
use Illuminate\Support\Collection;

interface GrowthPurchaseRepositoryInterface
{
    /**
     * 購入履歴を購入日の新しい順に取得
     *
     * @return Collection<int, GrowthPurchase>
     */
    public function all(): Collection;

    /**
     * @param  array{purchased_on: string, amount: int, fund_name: string, note?: string|null}  $data
     */
    public function create(array $data): GrowthPurchase;

    /**
     * @return bool 削除できたか
     */
    public function delete(int $id): bool;

    /**
     * 指定年の購入額合計（円）
     */
    public function sumAmountByYear(int $year): int;

    /**
     * 全期間の購入額合計（円）
     */
    public function sumAmount(): int;
}
