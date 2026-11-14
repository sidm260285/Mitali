<?php

namespace App\Models;

use Database\Factories\SessionBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionBatch extends Model
{
    /** @use HasFactory<SessionBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'session_id',
        'start_time',
        'end_time',
        'buffer_time',
        'max_size',
        'admission_counter',
        'attendance_counter',
    ];

    protected function casts(): array
    {
        return [
            'buffer_time' => 'integer',
            'max_size' => 'integer',
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
