<?php

namespace App\Http\Requests;

use App\Models\Session;
use App\Services\SessionDateValidator;
use App\Services\SessionMembershipValidator;
use App\Services\SessionStatusValidator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        /** @var Session $session */
        $session = $this->route('session');

        if ($session->isOver()) {
            return [
                'status' => [
                    'required',
                    Rule::in(app(SessionStatusValidator::class)->allowedNextStatuses($session)),
                ],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('training_sessions', 'name')->ignore($session->id)],
            'from_date' => ['required', 'date'],
            'to_date' => ['required', 'date'],
            'extended_date' => ['required', 'date'],
            'status' => [
                'required',
                Rule::in(app(SessionStatusValidator::class)->allowedNextStatuses($session)),
            ],
            'form_fee' => ['required', 'integer', 'min:0'],
            'ages' => ['required', 'array', 'min:1'],
            'ages.*.id' => ['nullable', 'integer', 'exists:session_ages,id'],
            'ages.*.from_age' => ['required', 'integer', 'min:1', 'max:150'],
            'ages.*.to_age' => ['required', 'integer', 'min:1', 'max:150'],
            'ages.*.fee' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'ages.*.extended_fee' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'ages.*.extended_fee_late' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'batches' => ['required', 'array', 'min:1'],
            'batches.*.id' => ['nullable', 'integer', 'exists:session_batches,id'],
            'batches.*.start_hour' => ['required', 'integer', 'min:1', 'max:12'],
            'batches.*.start_minute' => ['required', 'integer', 'min:0', 'max:59'],
            'batches.*.start_period' => ['required', Rule::in(['AM', 'PM'])],
            'batches.*.end_hour' => ['required', 'integer', 'min:1', 'max:12'],
            'batches.*.end_minute' => ['required', 'integer', 'min:0', 'max:59'],
            'batches.*.end_period' => ['required', Rule::in(['AM', 'PM'])],
            'batches.*.buffer_time' => ['required', 'integer', 'min:0', 'max:60'],
            'batches.*.max_size' => ['required', 'integer', 'min:0'],
            'memberships' => ['required', 'array', 'min:1'],
            'memberships.*.id' => ['nullable', 'integer', 'exists:session_memberships,id'],
            'memberships.*.card_name' => ['required', 'string', 'max:200'],
            'memberships.*.no_of_slot' => ['required', 'integer', Rule::in(SessionMembershipValidator::ALLOWED_SLOTS)],
            'memberships.*.membership_cost' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
        ];
    }

    public function withValidator($validator): void
    {
        /** @var Session $session */
        $session = $this->route('session');

        if ($session->isOver()) {
            return;
        }

        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $message = app(SessionDateValidator::class)->validate(
                $this->input('from_date'),
                $this->input('to_date'),
                $this->input('extended_date'),
            );

            if ($message) {
                $validator->errors()->add('from_date', $message);
            }
        });
    }
}
