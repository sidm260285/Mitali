<?php

namespace App\Services;

use App\Models\Trainer;
use App\Models\TrainerDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TrainerDocumentService
{
    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    private const PDF_MIME = 'application/pdf';

    public function store(Trainer $trainer, string $type, UploadedFile $file): TrainerDocument
    {
        $this->assertTrainerCanManageDocuments($trainer);
        $this->assertValidType($type);
        $this->assertProfileImageRules($trainer, $type);

        $mime = (string) $file->getMimeType();
        $this->assertAllowedMime($type, $mime);

        if ($mime === self::PDF_MIME) {
            $this->assertWithinSizeLimit($file->getSize());
            $storedPath = $this->storeRawFile($trainer, $file);
            $fileSize = $file->getSize();
        } else {
            [$storedPath, $fileSize, $mime] = $this->processAndStoreImage($trainer, $file);
        }

        return $trainer->documents()->create([
            'type' => $type,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size' => $fileSize,
        ]);
    }

    public function delete(TrainerDocument $document): void
    {
        $this->assertTrainerCanManageDocuments($document->trainer);

        $disk = $document->disk();

        if (Storage::disk($disk)->exists($document->file_path)) {
            Storage::disk($disk)->delete($document->file_path);
        }

        $document->delete();
    }

    public function directoryFor(Trainer $trainer): string
    {
        return 'trainer/'.$trainer->id;
    }

    private function assertTrainerCanManageDocuments(Trainer $trainer): void
    {
        if (! $trainer->isActive()) {
            throw ValidationException::withMessages([
                'file' => 'Documents cannot be modified while the trainer is inactive.',
            ]);
        }
    }

    private function assertValidType(string $type): void
    {
        if (! in_array($type, TrainerDocument::uploadableTypes(), true)) {
            throw ValidationException::withMessages([
                'type' => 'Invalid document type.',
            ]);
        }
    }

    private function assertProfileImageRules(Trainer $trainer, string $type): void
    {
        if ($type !== TrainerDocument::TYPE_PROFILE_IMAGE) {
            return;
        }

        if ($trainer->profileImage()->exists()) {
            throw ValidationException::withMessages([
                'file' => 'A profile image already exists. Delete it before uploading a new one.',
            ]);
        }
    }

    private function assertAllowedMime(string $type, string $mime): void
    {
        $allowed = $type === TrainerDocument::TYPE_PROFILE_IMAGE
            ? self::IMAGE_MIMES
            : [...self::IMAGE_MIMES, self::PDF_MIME];

        if (! in_array($mime, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => 'Only JPG, JPEG, PNG, WEBP, and PDF files are allowed.',
            ]);
        }
    }

    private function assertWithinSizeLimit(int $bytes): void
    {
        if ($bytes > $this->maxBytes()) {
            throw ValidationException::withMessages([
                'file' => sprintf('File must not exceed %d MB.', config('trainer.max_file_size_mb')),
            ]);
        }
    }

    private function storeRawFile(Trainer $trainer, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $this->directoryFor($trainer).'/'.$filename;

        Storage::disk($this->disk())->putFileAs(
            $this->directoryFor($trainer),
            $file,
            $filename,
        );

        return $path;
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     */
    private function processAndStoreImage(Trainer $trainer, UploadedFile $file): array
    {
        if (! extension_loaded('gd')) {
            $this->assertWithinSizeLimit($file->getSize());
            $storedPath = $this->storeRawFile($trainer, $file);

            return [$storedPath, $file->getSize(), (string) $file->getMimeType()];
        }

        $mime = (string) $file->getMimeType();
        $source = $this->loadImage($file->getRealPath(), $mime);
        $width = imagesx($source);
        $height = imagesy($source);

        $maxDimension = (int) config('trainer.image_max_dimension', 900);
        [$newWidth, $newHeight] = $this->scaledDimensions($width, $height, $maxDimension);

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        $filename = Str::uuid()->toString().'.jpg';
        $absolutePath = $this->absolutePath($trainer, $filename);
        $this->ensureDirectoryExists(dirname($absolutePath));

        $quality = 85;
        do {
            imagejpeg($canvas, $absolutePath, $quality);
            $fileSize = filesize($absolutePath) ?: 0;
            $quality -= 10;
        } while ($fileSize > $this->maxBytes() && $quality >= 40);

        imagedestroy($canvas);

        if ($fileSize > $this->maxBytes()) {
            @unlink($absolutePath);

            throw ValidationException::withMessages([
                'file' => sprintf('Image could not be reduced below %d MB.', config('trainer.max_file_size_mb')),
            ]);
        }

        return [$this->directoryFor($trainer).'/'.$filename, $fileSize, 'image/jpeg'];
    }

    private function loadImage(string $path, string $mime)
    {
        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path),
            'image/png' => imagecreatefrompng($path),
            'image/webp' => imagecreatefromwebp($path),
            default => throw ValidationException::withMessages([
                'file' => 'Unsupported image format.',
            ]),
        };
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function scaledDimensions(int $width, int $height, int $maxDimension): array
    {
        if ($width <= $maxDimension && $height <= $maxDimension) {
            return [$width, $height];
        }

        $ratio = min($maxDimension / $width, $maxDimension / $height);

        return [
            (int) round($width * $ratio),
            (int) round($height * $ratio),
        ];
    }

    private function disk(): string
    {
        return (string) config('trainer.storage_disk');
    }

    private function maxBytes(): int
    {
        return (int) config('trainer.max_file_size_mb') * 1024 * 1024;
    }

    private function absolutePath(Trainer $trainer, string $filename): string
    {
        return Storage::disk($this->disk())->path($this->directoryFor($trainer).'/'.$filename);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
