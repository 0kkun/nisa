<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\BacktestRequest;
use App\Services\Backtest\BacktestServiceInterface;
use Illuminate\View\View;

class BacktestController extends Controller
{
    public function __construct(
        private readonly BacktestServiceInterface $backtestService,
    ) {}

    public function index(BacktestRequest $request): View
    {
        $availableYears = $this->backtestService->availableYears();
        $result = $this->backtestService->run($request->fromYear(), $request->toYear());

        return view('backtest.index', compact('availableYears', 'result'));
    }
}
