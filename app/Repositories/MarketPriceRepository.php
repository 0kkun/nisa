<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\MarketSymbol;
use App\Models\MarketPrice;

class MarketPriceRepository implements MarketPriceRepositoryInterface
{
    private const CHUNK_SIZE = 1000;

    public function upsertCloses(MarketSymbol $symbol, array $closes): int
    {
        $now = now();
        $rows = [];

        foreach ($closes as $date => $close) {
            $rows[] = [
                'symbol' => $symbol->value,
                'date' => $date,
                'close' => $close,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($rows, self::CHUNK_SIZE) as $chunk) {
            MarketPrice::upsert($chunk, ['symbol', 'date'], ['close', 'updated_at']);
        }

        return count($rows);
    }

    public function latestDate(MarketSymbol $symbol): ?string
    {
        $date = MarketPrice::where('symbol', $symbol->value)->max('date');

        return $date === null ? null : substr((string) $date, 0, 10);
    }

    public function getCloses(MarketSymbol $symbol, ?string $from = null): array
    {
        return MarketPrice::query()
            ->where('symbol', $symbol->value)
            ->when($from !== null, fn ($query) => $query->where('date', '>=', $from))
            ->orderBy('date')
            ->toBase()
            ->get(['date', 'close'])
            ->mapWithKeys(fn (object $row) => [substr((string) $row->date, 0, 10) => (float) $row->close])
            ->all();
    }
}
