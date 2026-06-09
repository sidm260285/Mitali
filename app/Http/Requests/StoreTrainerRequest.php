<?php

namespace App\Http\Requests;

use App\Models\Trainer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return $this->baseRules();
    }

    protected function baseRules(?int $trainerId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'dob' => ['required', 'date', 'before_or_equal:'.now()->subYears(10)->format('Y-m-d')],
            'gender' => ['required', Rule::in(array_keys(Trainer::genderOptions()))],
            'permanent_address' => ['required', 'string', 'max:2000'],
            'current_address' => ['required', 'string', 'max:2000'],
            'monthly_salary' => ['required', 'integer', 'min:0'],
            'mobile' => [
                'required',
                'digits:10',
                'regex:/^[6-9]\d{9}$/',
                Rule::unique('trainers', 'mobile')->ignore($trainerId),
            ],
            'alternative_mobile' => ['nullable', 'digits:10', 'regex:/^[6-9]\d{9}$/'],
            'joining_date' => ['required', 'date', 'before_or_equal:today'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_ifsc' => ['nullable', 'string', 'max:20'],
        ];
    }
}
