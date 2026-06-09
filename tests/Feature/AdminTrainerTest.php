<?php

namespace Tests\Feature;

use App\Models\Trainer;
use App\Models\TrainerDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeImage;
use Tests\TestCase;

class AdminTrainerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('trainer_uploads');
        config([
            'trainer.storage_disk' => 'trainer_uploads',
            'trainer.image_max_dimension' => 900,
            'trainer.max_file_size_mb' => 2,
        ]);

        $this->admin = User::factory()->admin()->create();
    }

    private function validTrainerPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ravi Kumar',
            'dob' => now()->subYears(25)->format('Y-m-d'),
            'gender' => Trainer::GENDER_MALE,
            'permanent_address' => 'Permanent address line',
            'current_address' => 'Current address line',
            'monthly_salary' => 30000,
            'mobile' => '9876543210',
            'alternative_mobile' => '8765432109',
            'joining_date' => now()->subMonth()->format('Y-m-d'),
            'bank_account_number' => '1234567890',
            'bank_name' => 'State Bank',
            'bank_ifsc' => 'SBIN0001234',
        ], $overrides);
    }

    public function test_admin_can_view_trainer_list_defaulting_to_active(): void
    {
        Trainer::factory()->create(['name' => 'Active Trainer', 'is_active' => true]);
        Trainer::factory()->inactive()->create(['name' => 'Inactive Trainer']);

        $this->actingAs($this->admin)
            ->get(route('admin.trainers.index'))
            ->assertOk()
            ->assertSee('Trainers')
            ->assertSee('Active Trainer')
            ->assertDontSee('Inactive Trainer');
    }

    public function test_admin_can_filter_inactive_trainers(): void
    {
        Trainer::factory()->inactive()->create(['name' => 'Inactive Trainer']);

        $this->actingAs($this->admin)
            ->get(route('admin.trainers.index', ['status' => 'inactive']))
            ->assertOk()
            ->assertSee('Inactive Trainer');
    }

    public function test_admin_can_create_trainer(): void
    {
        $payload = $this->validTrainerPayload(['name' => 'New Trainer', 'mobile' => '9123456789']);

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.store'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $trainer = Trainer::where('mobile', '9123456789')->first();
        $this->assertNotNull($trainer);
        $this->assertSame('New Trainer', $trainer->name);
        $this->assertSame('New Trainer', $trainer->bank_account_holder_name);
        $this->assertTrue($trainer->is_active);
    }

    public function test_trainer_creation_rejects_future_joining_date_and_young_dob(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.trainers.store'), $this->validTrainerPayload([
                'dob' => now()->subYears(5)->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors('dob');

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.store'), $this->validTrainerPayload([
                'joining_date' => now()->addDay()->format('Y-m-d'),
                'mobile' => '9111111111',
            ]))
            ->assertSessionHasErrors('joining_date');
    }

    public function test_admin_can_search_trainers_by_name_and_phone(): void
    {
        Trainer::factory()->create(['name' => 'Alice Trainer', 'mobile' => '9000000001']);
        Trainer::factory()->create(['name' => 'Bob Trainer', 'mobile' => '9000000002']);

        $this->actingAs($this->admin)
            ->get(route('admin.trainers.index', ['status' => 'all', 'name' => 'Alice', 'phone' => '0001']))
            ->assertOk()
            ->assertSee('Alice Trainer')
            ->assertDontSee('Bob Trainer');
    }

    public function test_admin_can_view_trainer_profile_sections(): void
    {
        $trainer = Trainer::factory()->create(['name' => 'Profile Trainer']);

        $this->actingAs($this->admin)
            ->get(route('admin.trainers.show', $trainer))
            ->assertOk()
            ->assertSee('Personal Details')
            ->assertSee('Bank Details')
            ->assertSee('Profile Image')
            ->assertSee('Documents')
            ->assertSee('Profile Trainer');
    }

    public function test_admin_can_update_active_trainer(): void
    {
        $trainer = Trainer::factory()->create(['name' => 'Old Name', 'mobile' => '9222222222']);

        $this->actingAs($this->admin)
            ->put(route('admin.trainers.update', $trainer), $this->validTrainerPayload([
                'name' => 'Updated Name',
                'mobile' => '9222222222',
            ]))
            ->assertRedirect(route('admin.trainers.show', $trainer))
            ->assertSessionHas('success');

        $trainer->refresh();
        $this->assertSame('Updated Name', $trainer->name);
        $this->assertSame('Updated Name', $trainer->bank_account_holder_name);
    }

    public function test_inactive_trainer_cannot_be_edited(): void
    {
        $trainer = Trainer::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.trainers.edit', $trainer))
            ->assertRedirect(route('admin.trainers.show', $trainer));

        $this->actingAs($this->admin)
            ->put(route('admin.trainers.update', $trainer), $this->validTrainerPayload([
                'mobile' => $trainer->mobile,
            ]))
            ->assertForbidden();
    }

    public function test_admin_can_deactivate_and_activate_trainer(): void
    {
        $trainer = Trainer::factory()->create();

        $this->actingAs($this->admin)
            ->patch(route('admin.trainers.deactivate', $trainer))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($trainer->fresh()->is_active);

        $this->actingAs($this->admin)
            ->patch(route('admin.trainers.activate', $trainer))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($trainer->fresh()->is_active);
    }

    public function test_admin_can_upload_and_delete_documents(): void
    {
        $trainer = Trainer::factory()->create();

        $file = FakeImage::jpeg('certificate.jpg');

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.documents.store', $trainer), [
                'type' => TrainerDocument::TYPE_CERTIFICATE,
                'file' => $file,
            ])
            ->assertRedirect(route('admin.trainers.documents.index', $trainer))
            ->assertSessionHas('success');

        $document = $trainer->documents()->first();
        $this->assertNotNull($document);
        $this->assertSame(TrainerDocument::TYPE_CERTIFICATE, $document->type);
        Storage::disk('trainer_uploads')->assertExists($document->file_path);

        $this->actingAs($this->admin)
            ->get(route('admin.trainers.documents.show', [$trainer, $document]))
            ->assertOk();

        $this->actingAs($this->admin)
            ->delete(route('admin.trainers.documents.destroy', [$trainer, $document]))
            ->assertRedirect(route('admin.trainers.documents.index', $trainer));

        Storage::disk('trainer_uploads')->assertMissing($document->file_path);
        $this->assertDatabaseMissing('trainer_documents', ['id' => $document->id]);
    }

    public function test_profile_image_allows_only_one_file(): void
    {
        $trainer = Trainer::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.documents.store', $trainer), [
                'type' => TrainerDocument::TYPE_PROFILE_IMAGE,
                'file' => FakeImage::jpeg('profile.jpg'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.documents.store', $trainer), [
                'type' => TrainerDocument::TYPE_PROFILE_IMAGE,
                'file' => FakeImage::jpeg('profile-2.jpg'),
            ])
            ->assertSessionHasErrors('file');
    }

    public function test_inactive_trainer_cannot_manage_documents(): void
    {
        $trainer = Trainer::factory()->inactive()->create();

        $this->actingAs($this->admin)
            ->get(route('admin.trainers.documents.index', $trainer))
            ->assertRedirect(route('admin.trainers.show', $trainer));

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.documents.store', $trainer), [
                'type' => TrainerDocument::TYPE_PAN,
                'file' => UploadedFile::fake()->create('pan.pdf', 100, 'application/pdf'),
            ])
            ->assertForbidden();
    }

    public function test_mobile_must_be_unique_and_valid_indian_number(): void
    {
        Trainer::factory()->create(['mobile' => '9876543210']);

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.store'), $this->validTrainerPayload([
                'mobile' => '9876543210',
            ]))
            ->assertSessionHasErrors('mobile');

        $this->actingAs($this->admin)
            ->post(route('admin.trainers.store'), $this->validTrainerPayload([
                'mobile' => '5123456789',
            ]))
            ->assertSessionHasErrors('mobile');
    }

    public function test_executive_cannot_access_trainer_pages(): void
    {
        $executive = User::factory()->executive()->create();
        $trainer = Trainer::factory()->create();

        $this->actingAs($executive)
            ->get(route('admin.trainers.index'))
            ->assertForbidden();

        $this->actingAs($executive)
            ->get(route('admin.trainers.show', $trainer))
            ->assertForbidden();
    }
}
