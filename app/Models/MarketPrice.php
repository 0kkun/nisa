<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketPrice extends Model
{
    protected $fillable = [
        'symbol',
        'date',
        'close',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'immutable_date',
            'close' => 'float',
        ];
    }
}
