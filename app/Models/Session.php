<?php

namespace App\Models;

use App\Services\SessionMembershipValidator;
use App\Support\TimeHelper;
use Database\Factories\SessionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Session extends Model
{
    /** @use HasFactory<SessionFactory> */
    use HasFactory;

    protected $table = 'training_sessions';

    public const STATUS_UPCOMING = 'upcoming';

    public const STATUS_CURRENT = 'current';

    public const STATUS_OVER = 'over';

    protected $fillable = [
        'name',
        'status',
        'form_fee',
        'admission_count',
        'attendance_count',
    ];

    protected function casts(): array
    {
        return [
            'form_fee' => 'integer',
            'admission_count' => 'integer',
            'attendance_count' => 'integer',
        ];
    }

    public function ages(): HasMany
    {
        return $this->hasMany(SessionAge::class)->orderBy('from_age');
    }

    public function batches(): HasMany
    {
        return $this->hasMany(SessionBatch::class)->orderBy('start_time');
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(SessionMembership::class)->orderBy('no_of_slot');
    }

    public function isOver(): bool
    {
        return $this->status === self::STATUS_OVER;
    }

    public function isCurrent(): bool
    {
        return $this->status === self::STATUS_CURRENT;
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    public function agesSummary(): string
    {
        return $this->ages
            ->map(fn (SessionAge $age) => sprintf(
                '%d–%d (₹%s, Admission: %d)',
                $age->from_age,
                $age->to_age,
                number_format((float) $age->fee, 2),
                $age->admission_counter
            ))
            ->implode("\n") ?: '—';
    }

    public function batchesSummary(): string
    {
        return $this->batches
            ->map(fn (SessionBatch $batch) => sprintf(
                '%s–%s (%dm, Max: %d, Admission: %d)',
                TimeHelper::format12Hour((string) $batch->start_time),
                TimeHelper::format12Hour((string) $batch->end_time),
                $batch->buffer_time,
                $batch->max_size,
                $batch->admission_counter
            ))
            ->implode("\n") ?: '—';
    }

    public function membershipsSummary(): string
    {
        return $this->memberships
            ->sortBy(fn (SessionMembership $membership) => $membership->no_of_slot === -1 ? 99 : $membership->no_of_slot)
            ->map(fn (SessionMembership $membership) => sprintf(
                '%s (%s, ₹%s, Admission: %d)',
                $membership->card_name,
                $membership->no_of_slot === -1 ? 'Any' : $membership->no_of_slot.'-Slot',
                number_format((float) $membership->membership_cost, 2),
                $membership->admission_counter
            ))
            ->implode("\n") ?: '—';
    }

    public function isDeletable(): bool
    {
        return $this->admission_count === 0 && $this->attendance_count === 0;
    }
}
