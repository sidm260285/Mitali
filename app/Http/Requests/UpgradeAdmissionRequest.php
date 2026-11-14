<?php

namespace App\Http\Requests;

use App\Models\CashTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpgradeAdmissionRequest extends FormRequest
{
    protected $errorBag = 'admissionUpgrade';

    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isExecutive());
    }

    public function rules(): array
    {
        return [
            'session_membership_id' => ['required', 'integer', 'exists:session_memberships,id'],
            'is_extended_membership' => ['required', 'boolean'],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer'],
            'payment_mode' => ['nullable', Rule::in([CashTransaction::MODE_CASH, CashTransaction::MODE_BANK])],
            'bank_id' => ['nullable', 'integer'],
            'transaction_id' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'is_extended_membership.required' => 'Please select whether this is an extended session.',
            'is_extended_membership.boolean' => 'Please select whether this is an extended session.',
        ];
    }
}
