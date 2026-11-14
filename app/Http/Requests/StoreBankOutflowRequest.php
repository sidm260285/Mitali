<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class StoreBankOutflowRequest extends CashFlowEntryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'bank_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_BANK)->where('is_active', true),
            ],
            'account_head_id' => ['required', 'integer', 'exists:account_heads,id'],
            'transaction_id' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cash_transactions', 'transaction_id')->where('user_id', $this->integer('bank_id')),
            ],
        ]);
    }
}
