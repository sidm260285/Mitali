<?php

namespace App\Models;

use Database\Factories\TrainerFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Trainer extends Model
{
    /** @use HasFactory<TrainerFactory> */
    use HasFactory;

    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    public const GENDER_OTHER = 'other';

    protected $fillable = [
        'name',
        'dob',
        'gender',
        'permanent_address',
        'current_address',
        'monthly_salary',
        'mobile',
        'alternative_mobile',
        'joining_date',
        'bank_account_number',
        'bank_account_holder_name',
        'bank_name',
        'bank_ifsc',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'joining_date' => 'date',
            'monthly_salary' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function documents(): HasMany
    {
        return $this->hasMany(TrainerDocument::class)->orderByDesc('created_at');
    }

    public function profileImage(): HasOne
    {
        return $this->hasOne(TrainerDocument::class)
            ->where('type', TrainerDocument::TYPE_PROFILE_IMAGE);
    }

    public function age(): int
    {
        return (int) $this->dob->age;
    }

    public function genderLabel(): string
    {
        return match ($this->gender) {
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
            self::GENDER_OTHER => 'Other',
            default => ucfirst($this->gender),
        };
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public static function genderOptions(): array
    {
        return [
            self::GENDER_MALE => 'Male',
            self::GENDER_FEMALE => 'Female',
            self::GENDER_OTHER => 'Other',
        ];
    }

    public function documentsGroupedByType(): array
    {
        $grouped = [];

        foreach (TrainerDocument::typeOptions() as $type => $label) {
            $grouped[$type] = $this->documents->where('type', $type)->values();
        }

        return $grouped;
    }
}
