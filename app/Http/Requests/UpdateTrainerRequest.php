<?php

namespace App\Http\Requests;

use App\Models\Trainer;

class UpdateTrainerRequest extends StoreTrainerRequest
{
    public function rules(): array
    {
        /** @var Trainer $trainer */
        $trainer = $this->route('trainer');

        if (! $trainer->isActive()) {
            abort(403, 'Inactive trainers cannot be edited.');
        }

        return $this->baseRules($trainer->id);
    }
}
