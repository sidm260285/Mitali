<?php

namespace Tests\Unit;

use App\Models\Trainer;
use App\Models\TrainerDocument;
use App\Services\TrainerDocumentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Support\FakeImage;
use Tests\TestCase;

class TrainerDocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrainerDocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('trainer_uploads');
        config([
            'trainer.storage_disk' => 'trainer_uploads',
            'trainer.image_max_dimension' => 900,
            'trainer.max_file_size_mb' => 2,
        ]);

        $this->service = app(TrainerDocumentService::class);
    }

    public function test_image_is_stored_for_trainer(): void
    {
        $trainer = Trainer::factory()->create();
        $file = FakeImage::jpeg('certificate.jpg');

        $document = $this->service->store($trainer, TrainerDocument::TYPE_CERTIFICATE, $file);

        Storage::disk('trainer_uploads')->assertExists($document->file_path);
        $this->assertSame('image/jpeg', $document->mime_type);
    }

    public function test_image_is_resized_to_max_dimension_when_gd_available(): void
    {
        if (! extension_loaded('gd')) {
            $this->markTestSkipped('GD extension is not available.');
        }

        $trainer = Trainer::factory()->create();
        $sourcePath = tempnam(sys_get_temp_dir(), 'largejpg');
        $image = imagecreatetruecolor(1800, 1200);
        imagejpeg($image, $sourcePath, 90);
        imagedestroy($image);

        $file = new UploadedFile($sourcePath, 'large.jpg', 'image/jpeg', null, true);

        $document = $this->service->store($trainer, TrainerDocument::TYPE_CERTIFICATE, $file);

        $absolutePath = Storage::disk('trainer_uploads')->path($document->file_path);
        [$width, $height] = getimagesize($absolutePath);

        $this->assertLessThanOrEqual(900, $width);
        $this->assertLessThanOrEqual(900, $height);
    }

    public function test_pdf_larger_than_limit_is_rejected(): void
    {
        $trainer = Trainer::factory()->create();
        $file = UploadedFile::fake()->create('large.pdf', 3000, 'application/pdf');

        $this->expectException(ValidationException::class);

        $this->service->store($trainer, TrainerDocument::TYPE_PAN, $file);
    }
}
