<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Admission extends Model
{
    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    public const GENDER_OTHER = 'other';

    public const RELATION_FATHER = 'father';

    public const RELATION_MOTHER = 'mother';

    public const RELATION_GUARDIAN = 'guardian';

    public const RELATION_SPOUSE = 'spouse';

    public const RELATION_OTHER = 'other';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_BLOCKED = 'blocked';

    public const STATUS_DEREGISTERED = 'deregistered';

    protected $fillable = [
        'session_id',
        'session_membership_id',
        'session_age_id',
        'cash_transaction_id',
        'full_name',
        'gender',
        'date_of_birth',
        'mobile_no',
        'guardian_name',
        'relation',
        'emergency_contact_no',
        'address',
        'police_station',
        'pin_code',
        'rfid_code',
        'is_wildcard',
        'amount',
        'payment_mode',
        'is_document_complete',
        'status',
        'attendance_count',
        'admitted_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'is_wildcard' => 'boolean',
            'amount' => 'decimal:2',
            'is_document_complete' => 'boolean',
            'attendance_count' => 'integer',
            'admitted_by' => 'integer',
        ];
    }

    public static function genders(): array
    {
        return [
            self::GENDER_MALE,
            self::GENDER_FEMALE,
            self::GENDER_OTHER,
        ];
    }

    public static function relations(): array
    {
        return [
            self::RELATION_FATHER,
            self::RELATION_MOTHER,
            self::RELATION_GUARDIAN,
            self::RELATION_SPOUSE,
            self::RELATION_OTHER,
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(Session::class);
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(SessionMembership::class, 'session_membership_id');
    }

    public function sessionAge(): BelongsTo
    {
        return $this->belongsTo(SessionAge::class, 'session_age_id');
    }

    public function cashTransaction(): BelongsTo
    {
        return $this->belongsTo(CashTransaction::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AdmissionSlot::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(AdmissionDocument::class);
    }

    public function admittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function refreshDocumentStatus(): void
    {
        $hasPhoto = $this->documents()->where('type', AdmissionDocument::TYPE_PHOTO)->exists();
        $hasId = $this->documents()->where('type', AdmissionDocument::TYPE_ID_PROOF)->exists();
        $hasFitness = $this->documents()->where('type', AdmissionDocument::TYPE_FITNESS)->exists();

        $this->update(['is_document_complete' => $hasPhoto && $hasId && $hasFitness]);
    }

    public function calculatedAge(): int
    {
        return (int) floor($this->date_of_birth->diffInYears(now()));
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    public function hasAttendance(): bool
    {
        return $this->attendance_count > 0;
    }

    public function statusLabel(): string
    {
        return ucfirst($this->status);
    }

    public function scopeForSession($query, int $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }
}
