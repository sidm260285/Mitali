<?php

namespace App\Http\Requests;

use App\Models\Admission;
use App\Models\CashTransaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAdmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isExecutive());
    }

    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            'session_membership_id' => ['required', 'integer', 'exists:session_memberships,id'],
            'batch_ids' => ['nullable', 'array'],
            'batch_ids.*' => ['integer', 'exists:session_batches,id'],

            'full_name' => ['required', 'string', 'max:255'],
            'physically_disabled' => ['required', 'boolean'],
            'gender' => ['required', Rule::in(Admission::genders())],
            'date_of_birth' => Admission::dateOfBirthRules(),
            'mobile_no' => ['required', 'string', 'digits:10'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'relation' => ['required', Rule::in(Admission::relations())],
            'emergency_contact_no' => ['required', 'string', 'digits:10'],
            'address' => ['required', 'string', 'max:1000'],
            'police_station' => ['required', 'string', 'max:255'],
            'pin_code' => ['required', 'string', 'max:10'],
            'rfid_code' => ['required', 'string', 'max:100', Rule::unique('admissions')->where('session_id', $this->input('session_id'))],
            'is_extended_membership' => ['required', 'boolean'],

            'payment_mode' => ['required', Rule::in([CashTransaction::MODE_CASH, CashTransaction::MODE_BANK])],
            'bank_id' => ['required_if:payment_mode,bank', 'nullable', 'integer', 'exists:users,id'],
            'transaction_id' => ['required_if:payment_mode,bank', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'Member must be at least 1 year old.',
            'physically_disabled.required' => 'Please select whether the member is physically disabled.',
            'physically_disabled.boolean' => 'Please select whether the member is physically disabled.',
            'mobile_no.digits' => 'Mobile number must be exactly 10 digits.',
            'emergency_contact_no.digits' => 'Emergency contact number must be exactly 10 digits.',
            'rfid_code.unique' => 'This RFID code is already assigned to another member in this session.',
            'is_extended_membership.required' => 'Please select whether this is an extended session.',
            'is_extended_membership.boolean' => 'Please select whether this is an extended session.',
            'bank_id.required_if' => 'Please select a bank for bank payment mode.',
            'transaction_id.required_if' => 'Reference transaction ID is required for bank payment mode.',
        ];
    }
}
