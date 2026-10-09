<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreGrowthPurchaseRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StoreGrowthPurchaseRequestTest extends TestCase
{
    private function validData(): array
    {
        return [
            'purchased_on' => '2026-10-10',
            'amount' => 300_000,
            'fund_name' => 'eMAXIS Slim 米国株式(S&P500)',
            'note' => '急落時の買い増し',
        ];
    }

    public function test_正しい入力は通る(): void
    {
        $this->assertTrue($this->validate($this->validData())->passes());
    }

    public function test_メモは省略できる(): void
    {
        $data = $this->validData();
        unset($data['note']);

        $this->assertTrue($this->validate($data)->passes());
    }

    public static function invalidProvider(): array
    {
        return [
            '購入日なし' => ['purchased_on', null],
            '購入日が日付でない' => ['purchased_on', '2026-13-40'],
            '金額なし' => ['amount', null],
            '金額0' => ['amount', 0],
            '金額が年間枠超え' => ['amount', 2_400_001],
            '金額が数値でない' => ['amount', 'abc'],
            'ファンド名なし' => ['fund_name', ''],
            'ファンド名が長すぎる' => ['fund_name', str_repeat('a', 101)],
            'メモが長すぎる' => ['note', str_repeat('a', 256)],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_不正な入力はエラー(string $field, mixed $value): void
    {
        $validator = $this->validate([...$this->validData(), $field => $value]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey($field, $validator->errors()->toArray());
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, (new StoreGrowthPurchaseRequest)->rules());
    }
}
