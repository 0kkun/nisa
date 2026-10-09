<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('growth_purchases', function (Blueprint $table) {
            $table->id();
            $table->date('purchased_on');
            $table->unsignedInteger('amount')->comment('購入金額（円）');
            $table->string('fund_name', 100);
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index('purchased_on');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('growth_purchases');
    }
};
