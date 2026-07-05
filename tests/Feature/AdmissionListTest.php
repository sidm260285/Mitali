<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionSlot;
use App\Models\Session;
use App\Models\SessionAge;
use App\Models\SessionBatch;
use App\Models\SessionMembership;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionListTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $executive;

    private Session $session;

    private SessionBatch $batch;

    private SessionMembership $membership;

    private SessionAge $age;

    private Admission $admission;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountHeadSeeder::class);

        $this->admin = User::factory()->admin()->create();
        $this->executive = User::factory()->executive()->create();

        $this->session = Session::factory()->current()->create(['form_fee' => 100]);
        $this->age = SessionAge::factory()->create([
            'session_id' => $this->session->id,
            'from_age' => 1,
            'to_age' => 150,
            'fee' => 3000,
        ]);
        $this->batch = SessionBatch::factory()->create(['session_id' => $this->session->id]);
        $this->membership = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'no_of_slot' => 1,
            'membership_cost' => 500,
        ]);

        $this->admission = Admission::create([
            'session_id' => $this->session->id,
            'session_membership_id' => $this->membership->id,
            'session_age_id' => $this->age->id,
            'full_name' => 'Test Member',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(15)->toDateString(),
            'mobile_no' => '9876543210',
            'guardian_name' => 'Test Guardian',
            'relation' => 'father',
            'emergency_contact_no' => '9876543211',
            'address' => '123 Test Street',
            'police_station' => 'Test PS',
            'pin_code' => '700001',
            'rfid_code' => 'RFID-TEST-001',
            'is_wildcard' => false,
            'amount' => 3600,
            'payment_mode' => 'cash',
            'status' => 'active',
            'attendance_count' => 0,
            'admitted_by' => $this->admin->id,
        ]);

        AdmissionSlot::create([
            'admission_id' => $this->admission->id,
            'session_batch_id' => $this->batch->id,
        ]);
    }

    public function test_admin_can_view_admission_list(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.list', ['session_id' => $this->session->id]))
            ->assertOk()
            ->assertSee('Test Member')
            ->assertSee('Admission List');
    }

    public function test_executive_can_view_admission_list(): void
    {
        $this->actingAs($this->executive)
            ->get(route('executive.admission.list', ['session_id' => $this->session->id]))
            ->assertOk()
            ->assertSee('Test Member');
    }

    public function test_list_defaults_to_current_session(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.list'))
            ->assertOk()
            ->assertSee('Test Member');
    }

    public function test_list_can_filter_by_search(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.list', ['session_id' => $this->session->id, 'search' => 'RFID-TEST']))
            ->assertOk()
            ->assertSee('Test Member');

        $this->actingAs($this->admin)
            ->get(route('admin.admission.list', ['session_id' => $this->session->id, 'search' => 'NONEXIST']))
            ->assertOk()
            ->assertDontSee('Test Member');
    }

    public function test_list_can_filter_by_status(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.list', ['session_id' => $this->session->id, 'status' => 'blocked']))
            ->assertOk()
            ->assertDontSee('Test Member');
    }

    public function test_admin_can_view_admission_detail(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.show', $this->admission))
            ->assertOk()
            ->assertSee('Test Member')
            ->assertSee('RFID-TEST-001')
            ->assertSee('Admission Details');
    }

    public function test_executive_can_view_admission_detail(): void
    {
        $this->actingAs($this->executive)
            ->get(route('executive.admission.show', $this->admission))
            ->assertOk()
            ->assertSee('Test Member');
    }

    public function test_admin_can_edit_admission(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.edit', $this->admission))
            ->assertOk()
            ->assertSee('Edit Admission');
    }

    public function test_admin_can_update_admission_name(): void
    {
        $payload = $this->editPayload(['full_name' => 'Updated Name']);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $payload)
            ->assertRedirect(route('admin.admission.show', $this->admission));

        $this->assertSame('Updated Name', $this->admission->fresh()->full_name);
    }

    public function test_executive_cannot_edit_name(): void
    {
        $payload = $this->editPayload(['full_name' => 'Hacked Name']);
        unset($payload['full_name']);

        $this->actingAs($this->executive)
            ->put(route('executive.admission.update', $this->admission), $payload)
            ->assertRedirect();

        $this->assertSame('Test Member', $this->admission->fresh()->full_name);
    }

    public function test_rfid_can_be_updated(): void
    {
        $payload = $this->editPayload(['rfid_code' => 'NEW-RFID-002']);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $payload)
            ->assertRedirect();

        $this->assertSame('NEW-RFID-002', $this->admission->fresh()->rfid_code);
    }

    public function test_rfid_uniqueness_enforced_on_update(): void
    {
        Admission::create([
            'session_id' => $this->session->id,
            'session_membership_id' => $this->membership->id,
            'session_age_id' => $this->age->id,
            'full_name' => 'Other Member',
            'gender' => 'female',
            'date_of_birth' => now()->subYears(10)->toDateString(),
            'mobile_no' => '9999999999',
            'guardian_name' => 'Other Guardian',
            'relation' => 'mother',
            'emergency_contact_no' => '9999999998',
            'address' => '456 Other Street',
            'police_station' => 'Other PS',
            'pin_code' => '700002',
            'rfid_code' => 'RFID-TAKEN',
            'is_wildcard' => false,
            'amount' => 3600,
            'payment_mode' => 'cash',
            'status' => 'active',
            'attendance_count' => 0,
            'admitted_by' => $this->admin->id,
        ]);

        $payload = $this->editPayload(['rfid_code' => 'RFID-TAKEN']);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $payload)
            ->assertSessionHasErrors('rfid_code');
    }

    public function test_membership_can_be_changed_without_attendance(): void
    {
        $newMembership = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Silver',
            'no_of_slot' => 2,
            'membership_cost' => 300,
        ]);

        $batch2 = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        $payload = $this->editPayload([
            'session_membership_id' => $newMembership->id,
            'batch_ids' => [$this->batch->id, $batch2->id],
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $payload)
            ->assertRedirect();

        $this->assertSame($newMembership->id, $this->admission->fresh()->session_membership_id);
    }

    public function test_membership_cannot_be_changed_with_attendance(): void
    {
        $this->admission->update(['attendance_count' => 3]);

        $newMembership = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Platinum',
            'no_of_slot' => -1,
            'membership_cost' => 800,
        ]);

        $payload = $this->editPayload(['session_membership_id' => $newMembership->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $payload)
            ->assertRedirect();

        $this->assertSame($this->membership->id, $this->admission->fresh()->session_membership_id);
    }

    public function test_admin_can_block_admission(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.admission.block', $this->admission))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('blocked', $this->admission->fresh()->status);
    }

    public function test_admin_can_unblock_admission(): void
    {
        $this->admission->update(['status' => 'blocked']);

        $this->actingAs($this->admin)
            ->patch(route('admin.admission.unblock', $this->admission))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('active', $this->admission->fresh()->status);
    }

    public function test_executive_can_block_admission(): void
    {
        $this->actingAs($this->executive)
            ->patch(route('executive.admission.block', $this->admission))
            ->assertRedirect();

        $this->assertSame('blocked', $this->admission->fresh()->status);
    }

    public function test_bank_user_cannot_access_admission_list(): void
    {
        $bank = User::factory()->bank()->create();

        $this->actingAs($bank)
            ->get(route('admin.admission.list'))
            ->assertForbidden();
    }

    public function test_contact_numbers_must_be_10_digits_on_update(): void
    {
        $payload = $this->editPayload([
            'mobile_no' => '12345',
            'emergency_contact_no' => '123',
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $payload)
            ->assertSessionHasErrors(['mobile_no', 'emergency_contact_no']);
    }

    private function editPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => $this->admission->full_name,
            'gender' => $this->admission->gender,
            'date_of_birth' => $this->admission->date_of_birth->format('Y-m-d'),
            'mobile_no' => $this->admission->mobile_no,
            'guardian_name' => $this->admission->guardian_name,
            'relation' => $this->admission->relation,
            'emergency_contact_no' => $this->admission->emergency_contact_no,
            'address' => $this->admission->address,
            'police_station' => $this->admission->police_station,
            'pin_code' => $this->admission->pin_code,
            'rfid_code' => $this->admission->rfid_code,
            'session_membership_id' => $this->admission->session_membership_id,
            'batch_ids' => [$this->batch->id],
        ], $overrides);
    }
}
