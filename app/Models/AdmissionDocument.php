<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionDocument extends Model
{
    public const TYPE_PHOTO = 'photo';

    public const TYPE_ID_PROOF = 'id_proof';

    public const TYPE_FITNESS = 'fitness_certificate';

    public const MAX_PHOTO = 1;

    public const MAX_ID_PROOF = 2;

    public const MAX_FITNESS = 5;

    protected $fillable = [
        'admission_id',
        'type',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
        ];
    }

    public static function types(): array
    {
        return [
            self::TYPE_PHOTO,
            self::TYPE_ID_PROOF,
            self::TYPE_FITNESS,
        ];
    }

    public static function maxForType(string $type): int
    {
        return match ($type) {
            self::TYPE_PHOTO => self::MAX_PHOTO,
            self::TYPE_ID_PROOF => self::MAX_ID_PROOF,
            self::TYPE_FITNESS => self::MAX_FITNESS,
            default => 0,
        };
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            self::TYPE_PHOTO => 'Photo',
            self::TYPE_ID_PROOF => 'Address & ID Proof',
            self::TYPE_FITNESS => 'Fitness Certificate',
            default => ucfirst($type),
        };
    }

    public function admission(): BelongsTo
    {
        return $this->belongsTo(Admission::class);
    }

    public function disk(): string
    {
        return (string) config('admission.storage_disk');
    }
}
