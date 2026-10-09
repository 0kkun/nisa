<?php

use App\Http\Controllers\BacktestController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PurchaseController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
Route::delete('/purchases/{purchase}', [PurchaseController::class, 'destroy'])->whereNumber('purchase')->name('purchases.destroy');

Route::get('/backtest', [BacktestController::class, 'index'])->name('backtest.index');
