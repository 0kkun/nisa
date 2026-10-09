<?php

use Illuminate\Support\Facades\Schedule;

// 米国市場の引け（日本時間 5〜6時）後に取得
Schedule::command('market:fetch')
    ->dailyAt('07:00')
    ->withoutOverlapping();
