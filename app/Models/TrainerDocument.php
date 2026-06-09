<?php

namespace App\Models;

use Database\Factories\TrainerDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class TrainerDocument extends Model
{
    /** @use HasFactory<TrainerDocumentFactory> */
    use HasFactory;

    public const TYPE_CERTIFICATE = 'certificate';

    public const TYPE_AADHAAR = 'aadhaar';

    public const TYPE_PAN = 'pan';

    public const TYPE_PROFILE_IMAGE = 'profile_image';

    protected $fillable = [
        'trainer_id',
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

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function typeLabel(): string
    {
        return self::typeOptions()[$this->type] ?? ucfirst($this->type);
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function disk(): string
    {
        return (string) config('trainer.storage_disk');
    }

    public function fileExists(): bool
    {
        return Storage::disk($this->disk())->exists($this->file_path);
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_CERTIFICATE => 'Certificate',
            self::TYPE_AADHAAR => 'Aadhaar',
            self::TYPE_PAN => 'PAN',
            self::TYPE_PROFILE_IMAGE => 'Profile Image',
        ];
    }

    public static function uploadableTypes(): array
    {
        return [
            self::TYPE_CERTIFICATE,
            self::TYPE_AADHAAR,
            self::TYPE_PAN,
            self::TYPE_PROFILE_IMAGE,
        ];
    }
}
