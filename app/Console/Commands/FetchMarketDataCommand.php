<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\MarketSymbol;
use App\Services\MarketData\MarketDataServiceInterface;
use Illuminate\Console\Command;
use RuntimeException;

class FetchMarketDataCommand extends Command
{
    protected $signature = 'market:fetch';

    protected $description = 'S&P500・VIX・ドル円の日足を取得する（初回は全期間、以降は差分）';

    public function __construct(
        private readonly MarketDataServiceInterface $marketDataService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('市場データを取得中...');

        try {
            $result = $this->marketDataService->sync();
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['銘柄', 'ティッカー', '保存件数'],
            array_map(
                fn (string $ticker, int $count) => [MarketSymbol::from($ticker)->label(), $ticker, $count],
                array_keys($result),
                $result,
            ),
        );

        return self::SUCCESS;
    }
}
