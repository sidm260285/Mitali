<?php

namespace App\Http\Requests;

use App\Models\AccountHead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountHeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('account_heads', 'name')->where(fn ($query) => $query->where('type', $this->input('type'))),
            ],
            'type' => ['required', Rule::in([AccountHead::TYPE_CREDIT, AccountHead::TYPE_DEBIT])],
        ];
    }
}
