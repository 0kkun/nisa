<?php

declare(strict_types=1);

namespace App\Services\Quota;

use App\Domain\Quota\QuotaSummary;
use App\Models\GrowthPurchase;
use App\Repositories\GrowthPurchaseRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class GrowthQuotaService implements GrowthQuotaServiceInterface
{
    public function __construct(
        private readonly GrowthPurchaseRepositoryInterface $growthPurchaseRepository,
        private readonly int $annualLimit,
        private readonly int $lifetimeLimit,
    ) {}

    public function summary(CarbonImmutable $today): QuotaSummary
    {
        $usedThisYear = $this->growthPurchaseRepository->sumAmountByYear($today->year);
        $usedLifetime = $this->growthPurchaseRepository->sumAmount();
        $remainingThisYear = max(0, $this->annualLimit - $usedThisYear);
        $remainingLifetime = max(0, $this->lifetimeLimit - $usedLifetime);

        return new QuotaSummary(
            year: $today->year,
            annualLimit: $this->annualLimit,
            usedThisYear: $usedThisYear,
            remainingThisYear: $remainingThisYear,
            lifetimeLimit: $this->lifetimeLimit,
            usedLifetime: $usedLifetime,
            remainingLifetime: $remainingLifetime,
            monthsLeft: 13 - $today->month,
            investable: min($remainingThisYear, $remainingLifetime),
        );
    }

    public function purchases(): Collection
    {
        return $this->growthPurchaseRepository->all();
    }

    public function record(array $data): GrowthPurchase
    {
        return $this->growthPurchaseRepository->create($data);
    }

    public function remove(int $id): bool
    {
        return $this->growthPurchaseRepository->delete($id);
    }
}
