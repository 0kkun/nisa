<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GrowthPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrowthPurchase extends Model
{
    /** @use HasFactory<GrowthPurchaseFactory> */
    use HasFactory;

    protected $fillable = [
        'purchased_on',
        'amount',
        'fund_name',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'purchased_on' => 'immutable_date',
            'amount' => 'integer',
        ];
    }
}
