<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeBreakdown extends Model
{
    protected $fillable = [
        'session_id',
        'admission_id',
        'membership_amount',
        'age_fee',
        'form_fee',
        'extended_fee',
    ];

    protected function casts(): array
    {
        return [
            'membership_amount' => 'decimal:2',
            'age_fee' => 'decimal:2',
            'form_fee' => 'decimal:2',
            'extended_fee' => 'decimal:2',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }
}
