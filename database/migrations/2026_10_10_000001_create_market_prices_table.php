<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_prices', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 16)->comment('Yahoo Finance のティッカー');
            $table->date('date');
            $table->decimal('close', 14, 4);
            $table->timestamps();

            $table->unique(['symbol', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_prices');
    }
};
