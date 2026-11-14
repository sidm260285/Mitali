<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreBankRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('users', 'username')],
            'account_no' => ['required', 'string', 'min:8', 'max:200'],
            'account_type' => ['required', 'string', Rule::in(array_keys(User::accountTypeOptions()))],
            'branch_name' => ['required', 'string', 'max:200'],
            'phone' => ['nullable', 'digits:10', Rule::unique('users', 'phone')->where('role', User::ROLE_BANK)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')->where('role', User::ROLE_BANK)],
            'password' => ['required', Password::defaults()],
        ];
    }
}
