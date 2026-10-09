<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Timing;

use App\Domain\Timing\InvestmentPlanner;
use App\Domain\Timing\TimingLevel;
use PHPUnit\Framework\TestCase;

class InvestmentPlannerTest extends TestCase
{
    private InvestmentPlanner $planner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->planner = new InvestmentPlanner(roundingUnit: 10_000);
    }

    public function test_残り枠を残り月数で割り倍率を掛ける(): void
    {
        // 2,400,000 / 12 = 200,000 × 0.5
        $this->assertSame(100_000, $this->planner->monthlyAmount(2_400_000, 12, $this->level(0.5)));
        // 2,400,000 / 12 = 200,000 × 2
        $this->assertSame(400_000, $this->planner->monthlyAmount(2_400_000, 12, $this->level(2.0)));
    }

    public function test_丸め単位で切り捨てる(): void
    {
        // 1,000,000 / 7 = 142,857 × 1 → 140,000
        $this->assertSame(140_000, $this->planner->monthlyAmount(1_000_000, 7, $this->level(1.0)));
    }

    public function test_残り枠を超えない(): void
    {
        $this->assertSame(500_000, $this->planner->monthlyAmount(500_000, 2, $this->level(3.0)));
    }

    public function test_最終月は残り全額(): void
    {
        $this->assertSame(123_456, $this->planner->monthlyAmount(123_456, 1, $this->level(0.5)));
    }

    public function test_残り枠がなければ0(): void
    {
        $this->assertSame(0, $this->planner->monthlyAmount(0, 5, $this->level(3.0)));
    }

    private function level(float $multiplier): TimingLevel
    {
        return new TimingLevel('test', 'テスト', 0, $multiplier);
    }
}
