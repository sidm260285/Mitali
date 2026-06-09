<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreExecutiveRequest extends FormRequest
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
            'phone' => ['required', 'digits:10'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'address' => ['nullable', 'string', 'max:1000'],
            'password' => ['required', Password::defaults()],
        ];
    }
}
