<?php

namespace App\Services;

use App\Models\Admission;
use App\Models\AdmissionDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdmissionDocumentService
{
    private const IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/webp',
    ];

    public function store(Admission $admission, string $type, UploadedFile $file): AdmissionDocument
    {
        $this->assertValidType($type);
        $this->assertMaxCount($admission, $type);
        $this->assertAllowedMime($file);
        $this->assertWithinUploadLimit($file);

        [$storedPath, $fileSize, $mime] = $this->processAndStoreImage($admission, $file, $type);

        $document = $admission->documents()->create([
            'type' => $type,
            'file_path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $mime,
            'file_size' => $fileSize,
        ]);

        $admission->refreshDocumentStatus();

        return $document;
    }

    public function delete(AdmissionDocument $document): void
    {
        $admission = $document->admission;
        $disk = $this->disk();

        if (Storage::disk($disk)->exists($document->file_path)) {
            Storage::disk($disk)->delete($document->file_path);
        }

        $document->delete();
        $admission->refreshDocumentStatus();
    }

    private function assertValidType(string $type): void
    {
        if (! in_array($type, AdmissionDocument::types(), true)) {
            throw ValidationException::withMessages([
                'type' => 'Invalid document type.',
            ]);
        }
    }

    private function assertMaxCount(Admission $admission, string $type): void
    {
        $current = $admission->documents()->where('type', $type)->count();
        $max = AdmissionDocument::maxForType($type);

        if ($current >= $max) {
            throw ValidationException::withMessages([
                'file' => sprintf(
                    'Maximum %d file(s) allowed for %s. Delete an existing file first.',
                    $max,
                    AdmissionDocument::typeLabel($type),
                ),
            ]);
        }
    }

    private function assertAllowedMime(UploadedFile $file): void
    {
        $mime = (string) $file->getMimeType();

        if (! in_array($mime, self::IMAGE_MIMES, true)) {
            throw ValidationException::withMessages([
                'file' => 'Only JPG, PNG, and WEBP images are allowed.',
            ]);
        }
    }

    private function assertWithinUploadLimit(UploadedFile $file): void
    {
        $maxBytes = (int) config('admission.max_upload_size_mb') * 1024 * 1024;

        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => sprintf('File must not exceed %d MB.', config('admission.max_upload_size_mb')),
            ]);
        }
    }

    /**
     * @return array{0: string, 1: int, 2: string}
     */
    private function processAndStoreImage(Admission $admission, UploadedFile $file, string $type): array
    {
        if (! extension_loaded('gd')) {
            $storedPath = $this->storeRawFile($admission, $file);

            return [$storedPath, $file->getSize(), (string) $file->getMimeType()];
        }

        $mime = (string) $file->getMimeType();
        $source = $this->loadImage($file->getRealPath(), $mime);
        $width = imagesx($source);
        $height = imagesy($source);

        $maxDimension = $type === AdmissionDocument::TYPE_PHOTO
            ? (int) config('admission.photo_max_dimension', 800)
            : (int) config('admission.document_max_dimension', 1200);

        [$newWidth, $newHeight] = $this->scaledDimensions($width, $height, $maxDimension);

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($source);

        $filename = Str::uuid()->toString().'.jpg';
        $absolutePath = $this->absolutePath($admission, $filename);
        $this->ensureDirectoryExists(dirname($absolutePath));

        $quality = (int) config('admission.jpeg_quality', 80);
        $targetBytes = (int) config('admission.target_file_size_kb', 500) * 1024;

        do {
            imagejpeg($canvas, $absolutePath, $quality);
            $fileSize = filesize($absolutePath) ?: 0;
            $quality -= 10;
        } while ($fileSize > $targetBytes && $quality >= 40);

        imagedestroy($canvas);

        return [$this->directoryFor($admission).'/'.$filename, $fileSize, 'image/jpeg'];
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

    private function storeRawFile(Admission $admission, UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $filename = Str::uuid()->toString().'.'.$extension;

        Storage::disk($this->disk())->putFileAs(
            $this->directoryFor($admission),
            $file,
            $filename,
        );

        return $this->directoryFor($admission).'/'.$filename;
    }

    private function directoryFor(Admission $admission): string
    {
        return $admission->session_id.'/'.$admission->id;
    }

    private function disk(): string
    {
        return (string) config('admission.storage_disk');
    }

    private function absolutePath(Admission $admission, string $filename): string
    {
        return Storage::disk($this->disk())->path($this->directoryFor($admission).'/'.$filename);
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }
}
