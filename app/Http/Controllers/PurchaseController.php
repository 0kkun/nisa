<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreGrowthPurchaseRequest;
use App\Services\Quota\GrowthQuotaServiceInterface;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        private readonly GrowthQuotaServiceInterface $growthQuotaService,
    ) {}

    public function index(): View
    {
        $quota = $this->growthQuotaService->summary(CarbonImmutable::today());
        $purchases = $this->growthQuotaService->purchases();

        return view('purchases.index', compact('quota', 'purchases'));
    }

    public function store(StoreGrowthPurchaseRequest $request): RedirectResponse
    {
        $this->growthQuotaService->record($request->toData());

        return redirect()->route('purchases.index')->with('status', '購入を登録しました。');
    }

    public function destroy(int $purchase): RedirectResponse
    {
        abort_unless($this->growthQuotaService->remove($purchase), 404);

        return redirect()->route('purchases.index')->with('status', '購入を削除しました。');
    }
}
