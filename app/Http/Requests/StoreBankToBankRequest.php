<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StoreBankToBankRequest extends CashFlowEntryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'from_bank_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('role', User::ROLE_BANK)->where('is_active', true),
            ],
            'to_bank_id' => [
                'required',
                'integer',
                'different:from_bank_id',
                Rule::exists('users', 'id')->where('role', User::ROLE_BANK)->where('is_active', true),
            ],
            'transaction_id' => [
                'required',
                'string',
                'max:255',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $fromBankId = $this->integer('from_bank_id');
                    $exists = DB::table('cash_transactions')
                        ->where('user_id', $fromBankId)
                        ->where('transaction_id', $value)
                        ->exists();
                    if ($exists) {
                        $fail('This transaction ID has already been used for this bank.');
                    }
                },
            ],
        ]);
    }

    public function messages(): array
    {
        return [
            'to_bank_id.different' => 'From bank and To bank must be different.',
        ];
    }
}
