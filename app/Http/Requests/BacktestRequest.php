<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BacktestRequest extends FormRequest
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
            'from_year' => ['nullable', 'integer', 'between:1900,2100'],
            'to_year' => ['nullable', 'integer', 'between:1900,2100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'from_year' => '開始年',
            'to_year' => '終了年',
        ];
    }

    public function fromYear(): ?int
    {
        return $this->filled('from_year') ? (int) $this->validated('from_year') : null;
    }

    public function toYear(): ?int
    {
        return $this->filled('to_year') ? (int) $this->validated('to_year') : null;
    }
}
