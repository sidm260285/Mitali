<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class StoreExecutiveTransferRequest extends CashFlowEntryRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isExecutive() ?? false;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'executive_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')
                    ->where('role', 'executive')
                    ->where('is_active', true)
                    ->where('id', '!=', $this->user()->id),
            ],
        ]);
    }
}
