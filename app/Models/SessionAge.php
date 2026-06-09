<?php

namespace App\Models;

use Database\Factories\SessionAgeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAge extends Model
{
    /** @use HasFactory<SessionAgeFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'from_age',
        'to_age',
        'fee',
        'admission_counter',
        'attendance_counter',
    ];

    protected function casts(): array
    {
        return [
            'from_age' => 'integer',
            'to_age' => 'integer',
            'fee' => 'decimal:2',
            'admission_counter' => 'integer',
            'attendance_counter' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function isFrozen(): bool
    {
        return $this->admission_counter > 0;
    }
}
