<?php

declare(strict_types=1);

namespace App\Services\Quota;

use App\Domain\Quota\QuotaSummary;
use App\Models\GrowthPurchase;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface GrowthQuotaServiceInterface
{
    /**
     * 指定日時点の成長投資枠の利用状況
     */
    public function summary(CarbonImmutable $today): QuotaSummary;

    /**
     * @return Collection<int, GrowthPurchase>
     */
    public function purchases(): Collection;

    /**
     * @param  array<string, mixed>  $data
     */
    public function record(array $data): GrowthPurchase;

    public function remove(int $id): bool;
}
