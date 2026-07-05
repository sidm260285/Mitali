<?php

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\AdmissionSlot;
use App\Models\CashTransaction;
use App\Models\Session;
use App\Models\SessionAge;
use App\Models\SessionBatch;
use App\Models\SessionMembership;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $executive;

    private Session $session;

    private SessionAge $age;

    private SessionBatch $batch;

    private SessionMembership $membership;

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
        $this->batch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'max_size' => 30,
        ]);
        $this->membership = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'no_of_slot' => 1,
            'membership_cost' => 500,
        ]);
    }

    public function test_admin_can_view_current_admission_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.current'))
            ->assertOk()
            ->assertSee('New Admission');
    }

    public function test_executive_can_view_current_admission_form(): void
    {
        $this->actingAs($this->executive)
            ->get(route('executive.admission.current'))
            ->assertOk()
            ->assertSee('New Admission');
    }

    public function test_admin_can_view_upcoming_admission_form(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.upcoming'))
            ->assertOk();
    }

    public function test_admin_can_submit_cash_admission(): void
    {
        $payload = $this->validPayload();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('admissions', [
            'session_id' => $this->session->id,
            'full_name' => 'John Doe',
            'rfid_code' => 'RFID001',
            'payment_mode' => 'cash',
            'amount' => 3600.00,
        ]);

        $admission = Admission::where('rfid_code', 'RFID001')->first();
        $this->assertNotNull($admission);
        $this->assertNotNull($admission->cash_transaction_id);
        $this->assertFalse($admission->is_wildcard);

        $this->assertDatabaseHas('admission_slots', [
            'admission_id' => $admission->id,
            'session_batch_id' => $this->batch->id,
        ]);

        $this->assertSame(1, $this->session->fresh()->admission_count);
        $this->assertSame(1, $this->age->fresh()->admission_counter);
        $this->assertSame(1, $this->membership->fresh()->admission_counter);
        $this->assertSame(1, $this->batch->fresh()->admission_counter);
    }

    public function test_admission_creates_cash_transaction_under_admission_head(): void
    {
        $payload = $this->validPayload();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload);

        $admission = Admission::where('rfid_code', 'RFID001')->first();
        $transaction = CashTransaction::find($admission->cash_transaction_id);

        $this->assertNotNull($transaction);
        $this->assertSame(CashTransaction::TYPE_CREDIT, $transaction->type);
        $this->assertSame(CashTransaction::MODE_CASH, $transaction->mode);
        $this->assertSame('3600.00', $transaction->amount);
        $this->assertSame($this->admin->id, $transaction->user_id);
    }

    public function test_bank_mode_admission_requires_bank_id_and_transaction_id(): void
    {
        $payload = $this->validPayload(['payment_mode' => 'bank']);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertSessionHasErrors(['bank_id', 'transaction_id']);
    }

    public function test_bank_mode_admission_credits_bank_account(): void
    {
        $bank = User::factory()->bank()->create();

        $payload = $this->validPayload([
            'payment_mode' => 'bank',
            'bank_id' => $bank->id,
            'transaction_id' => 'TXN-12345',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertRedirect();

        $admission = Admission::where('rfid_code', 'RFID001')->first();
        $transaction = CashTransaction::find($admission->cash_transaction_id);

        $this->assertSame($bank->id, $transaction->user_id);
        $this->assertSame(CashTransaction::MODE_BANK, $transaction->mode);
        $this->assertSame('TXN-12345', $transaction->transaction_id);
    }

    public function test_rfid_must_be_unique_per_session(): void
    {
        $payload = $this->validPayload();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertRedirect();

        $payload2 = $this->validPayload(['rfid_code' => 'RFID001', 'full_name' => 'Jane Doe']);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload2)
            ->assertSessionHasErrors('rfid_code');
    }

    public function test_wildcard_membership_selects_all_batches(): void
    {
        $wildcardMembership = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'All Access',
            'no_of_slot' => -1,
            'membership_cost' => 1000,
        ]);

        $batch2 = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        $payload = $this->validPayload([
            'session_membership_id' => $wildcardMembership->id,
            'batch_ids' => [],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertRedirect();

        $admission = Admission::where('rfid_code', 'RFID001')->first();
        $this->assertTrue($admission->is_wildcard);
        $this->assertSame(2, AdmissionSlot::where('admission_id', $admission->id)->count());
        $this->assertSame('4100.00', $admission->amount);
    }

    public function test_batch_capacity_is_enforced(): void
    {
        $this->batch->update(['max_size' => 1, 'admission_counter' => 1]);

        $payload = $this->validPayload();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertSessionHasErrors('batch_ids');
    }

    public function test_invalid_age_rejects_admission(): void
    {
        $this->age->update(['from_age' => 10, 'to_age' => 20]);

        $payload = $this->validPayload([
            'date_of_birth' => now()->subYears(5)->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertSessionHasErrors('date_of_birth');
    }

    public function test_session_data_endpoint_returns_json(): void
    {
        $this->actingAs($this->admin)
            ->getJson(route('admin.admission.session-data', $this->session))
            ->assertOk()
            ->assertJsonStructure([
                'batches' => [['id', 'label', 'available']],
                'memberships' => [['id', 'card_name', 'no_of_slot', 'membership_cost']],
                'ages' => [['from_age', 'to_age', 'fee']],
                'form_fee',
            ]);
    }

    public function test_executive_can_submit_admission(): void
    {
        $payload = $this->validPayload(['rfid_code' => 'RFID-EXEC']);

        $this->actingAs($this->executive)
            ->post(route('executive.admission.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('admissions', [
            'rfid_code' => 'RFID-EXEC',
            'admitted_by' => $this->executive->id,
        ]);
    }

    public function test_amount_calculation_includes_form_fee(): void
    {
        $payload = $this->validPayload();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertRedirect();

        $admission = Admission::where('rfid_code', 'RFID001')->first();
        $expected = 500 + 3000 + 100;
        $this->assertSame(number_format($expected, 2, '.', ''), $admission->amount);
    }

    public function test_wrong_slot_count_is_rejected(): void
    {
        $batch2 = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
        ]);

        $payload = $this->validPayload([
            'batch_ids' => [$this->batch->id, $batch2->id],
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertSessionHasErrors('batch_ids');
    }

    public function test_bank_user_cannot_access_admission(): void
    {
        $bank = User::factory()->bank()->create();

        $this->actingAs($bank)
            ->get(route('admin.admission.current'))
            ->assertForbidden();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'session_id' => $this->session->id,
            'session_membership_id' => $this->membership->id,
            'batch_ids' => [$this->batch->id],
            'full_name' => 'John Doe',
            'gender' => 'male',
            'date_of_birth' => now()->subYears(15)->toDateString(),
            'mobile_no' => '9876543210',
            'guardian_name' => 'Papa Doe',
            'relation' => 'father',
            'emergency_contact_no' => '9876543211',
            'address' => '123 Main Street',
            'police_station' => 'Main PS',
            'pin_code' => '700001',
            'rfid_code' => 'RFID001',
            'payment_mode' => 'cash',
        ], $overrides);
    }
}
