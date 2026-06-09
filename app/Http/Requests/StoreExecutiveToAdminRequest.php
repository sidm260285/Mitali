<?php

namespace App\Http\Requests;

class StoreExecutiveToAdminRequest extends CashFlowEntryRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isExecutive() ?? false;
    }
}
