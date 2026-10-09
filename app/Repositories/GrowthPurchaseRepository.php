<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\GrowthPurchase;
use Illuminate\Support\Collection;

class GrowthPurchaseRepository implements GrowthPurchaseRepositoryInterface
{
    public function all(): Collection
    {
        return GrowthPurchase::query()
            ->orderByDesc('purchased_on')
            ->orderByDesc('id')
            ->get();
    }

    public function create(array $data): GrowthPurchase
    {
        return GrowthPurchase::create($data);
    }

    public function delete(int $id): bool
    {
        return GrowthPurchase::whereKey($id)->delete() > 0;
    }

    public function sumAmountByYear(int $year): int
    {
        return (int) GrowthPurchase::query()
            ->where('purchased_on', '>=', "{$year}-01-01")
            ->where('purchased_on', '<', ($year + 1).'-01-01')
            ->sum('amount');
    }

    public function sumAmount(): int
    {
        return (int) GrowthPurchase::sum('amount');
    }
}
