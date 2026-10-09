<?php

declare(strict_types=1);

namespace App\Services\Timing;

use App\Domain\Timing\TimingAssessment;
use Carbon\CarbonImmutable;

interface TimingServiceInterface
{
    /**
     * 最新の市場データから買い場を判定し、今月の推奨投入額を出す
     *
     * @return TimingAssessment|null 市場データ未取得なら null
     */
    public function assess(CarbonImmutable $today): ?TimingAssessment;
}
