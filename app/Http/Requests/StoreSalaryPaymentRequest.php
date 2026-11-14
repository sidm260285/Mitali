<?php

namespace App\Http\Requests;

use App\Models\CashTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalaryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'payee_type' => ['required', Rule::in([CashTransaction::SALARY_TYPE_EXECUTIVE, CashTransaction::SALARY_TYPE_TRAINER])],
            'payee_id' => ['required', 'integer', 'min:1'],
            'salary_month' => ['required', 'integer', 'min:1', 'max:12'],
            'salary_year' => ['required', 'integer', 'min:2020', 'max:2099'],
            'amount' => ['required', 'numeric', 'min:0.01', 'decimal:0,2'],
            'mode' => ['required', Rule::in([CashTransaction::MODE_CASH, CashTransaction::MODE_BANK])],
            'bank_id' => ['required_if:mode,bank', 'nullable', 'integer'],
            'transaction_id' => [
                'required_if:mode,bank',
                'nullable',
                'string',
                'max:255',
                $this->input('mode') === CashTransaction::MODE_BANK && $this->filled('bank_id')
                    ? Rule::unique('cash_transactions', 'transaction_id')->where('user_id', $this->integer('bank_id'))
                    : null,
            ],
            'narration' => ['nullable', 'string', 'max:400'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->validateMonthYearNotTooFarInFuture($validator);
        });
    }

    private function validateMonthYearNotTooFarInFuture($validator): void
    {
        $month = $this->integer('salary_month');
        $year = $this->integer('salary_year');

        $now = now();
        $currentMonth = (int) $now->month;
        $currentYear = (int) $now->year;

        $selectedPeriod = $year * 12 + $month;
        $maxAllowedPeriod = $currentYear * 12 + $currentMonth + 1;

        if ($selectedPeriod > $maxAllowedPeriod) {
            $validator->errors()->add('salary_month', 'Salary can only be paid up to one month in the future.');
        }
    }
}
