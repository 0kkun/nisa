<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreGrowthPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'purchased_on' => ['required', 'date_format:Y-m-d'],
            'amount' => ['required', 'integer', 'min:1', 'max:'.config('nisa.growth_annual_limit', 2_400_000)],
            'fund_name' => ['required', 'string', 'max:100'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'purchased_on' => '購入日',
            'amount' => '金額',
            'fund_name' => 'ファンド名',
            'note' => 'メモ',
        ];
    }

    /**
     * @return array{purchased_on: string, amount: int, fund_name: string, note: string|null}
     */
    public function toData(): array
    {
        return [
            'purchased_on' => $this->validated('purchased_on'),
            'amount' => (int) $this->validated('amount'),
            'fund_name' => $this->validated('fund_name'),
            'note' => $this->validated('note'),
        ];
    }
}
