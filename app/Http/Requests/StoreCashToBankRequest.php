<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class StoreCashToBankRequest extends CashFlowEntryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'bank_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_BANK)->where('is_active', true),
            ],
        ]);
    }
}
