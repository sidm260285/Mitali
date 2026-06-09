<?php

namespace Database\Factories;

use App\Models\Trainer;
use App\Models\TrainerDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainerDocument>
 */
class TrainerDocumentFactory extends Factory
{
    protected $model = TrainerDocument::class;

    public function definition(): array
    {
        $type = fake()->randomElement([
            TrainerDocument::TYPE_CERTIFICATE,
            TrainerDocument::TYPE_AADHAAR,
            TrainerDocument::TYPE_PAN,
        ]);

        return [
            'trainer_id' => Trainer::factory(),
            'type' => $type,
            'file_path' => 'trainer/1/sample.pdf',
            'original_name' => 'document.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 1024,
        ];
    }

    public function profileImage(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TrainerDocument::TYPE_PROFILE_IMAGE,
            'file_path' => 'trainer/1/profile.jpg',
            'original_name' => 'profile.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
        ]);
    }
}
