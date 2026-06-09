<?php

namespace App\Http\Requests;

class StoreInflowRequest extends CashFlowEntryRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'account_head_id' => ['required', 'integer', 'exists:account_heads,id'],
        ]);
    }
}
