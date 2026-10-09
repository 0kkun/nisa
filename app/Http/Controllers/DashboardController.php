<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Timing\TimingServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly TimingServiceInterface $timingService,
    ) {}

    public function index(): View
    {
        $assessment = $this->timingService->assess(CarbonImmutable::today());

        return view('dashboard', compact('assessment'));
    }
}
