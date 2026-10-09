<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\BacktestRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BacktestRequestTest extends TestCase
{
    public function test_年の指定は省略できる(): void
    {
        $this->assertTrue($this->validate([])->passes());
    }

    public function test_開始年と終了年を指定できる(): void
    {
        $this->assertTrue($this->validate(['from_year' => '2010', 'to_year' => '2020'])->passes());
    }

    public static function invalidProvider(): array
    {
        return [
            '開始年が数値でない' => ['from_year', 'abc'],
            '開始年が古すぎる' => ['from_year', 1899],
            '終了年が未来すぎる' => ['to_year', 2101],
        ];
    }

    #[DataProvider('invalidProvider')]
    public function test_不正な入力はエラー(string $field, mixed $value): void
    {
        $validator = $this->validate([$field => $value]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey($field, $validator->errors()->toArray());
    }

    private function validate(array $data): \Illuminate\Validation\Validator
    {
        return Validator::make($data, (new BacktestRequest)->rules());
    }
}
