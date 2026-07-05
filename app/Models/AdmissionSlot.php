<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionSlot extends Model
{
    protected $fillable = [
        'admission_id',
        'session_batch_id',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(SessionBatch::class, 'session_batch_id');
    }
}
