<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class CashFlowEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $rules = [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0'],
            'narration' => ['nullable', 'string', 'max:400'],
        ];

        if (config('cashflow.allow_past_dates')) {
            $rules['transaction_date'] = ['required', 'date', 'before_or_equal:today'];
        }

        return $rules;
    }

    protected function passedValidation(): void
    {
        if (! config('cashflow.allow_past_dates')) {
            $this->merge(['transaction_date' => now()->toDateString()]);
        }
    }

    public function transactionDate(): string
    {
        return config('cashflow.allow_past_dates')
            ? $this->date('transaction_date')->toDateString()
            : now()->toDateString();
    }
}
