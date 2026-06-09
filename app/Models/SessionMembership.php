<?php

namespace App\Models;

use Database\Factories\SessionMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionMembership extends Model
{
    /** @use HasFactory<SessionMembershipFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'card_name',
        'no_of_slot',
        'membership_cost',
        'admission_counter',
        'attendance_counter',
    ];

    protected function casts(): array
    {
        return [
            'no_of_slot' => 'integer',
            'membership_cost' => 'decimal:2',
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
