<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExecutiveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        $executive = $this->route('executive');

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'required',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($executive->id),
            ],
            'phone' => [
                'required',
                'digits:10',
                Rule::unique('users', 'phone')->where('role', User::ROLE_EXECUTIVE)->ignore($executive->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where('role', User::ROLE_EXECUTIVE)->ignore($executive->id),
            ],
            'address' => ['nullable', 'string', 'max:1000'],
            'monthly_salary' => ['required', 'integer', 'min:0'],
        ];
    }
}
