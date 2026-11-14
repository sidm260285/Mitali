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

        $this->session = Session::factory()->current()->create([
            'form_fee' => 100,
            'from_date' => '2026-01-15',
            'to_date' => '2026-06-15',
            'extended_date' => '2026-09-15',
        ]);
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
            ->assertSee('New Admission')
            ->assertSee('Session Start Date')
            ->assertSee('Session End Date')
            ->assertSee('Extended Session')
            ->assertSee('id="feeBreakdown"', false);
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

    public function test_wildcard_admission_is_accepted_when_a_batch_is_full(): void
    {
        $wildcardMembership = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'All Access',
            'no_of_slot' => -1,
            'membership_cost' => 1000,
        ]);
        $fullBatch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
            'max_size' => 1,
            'admission_counter' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload([
                'session_membership_id' => $wildcardMembership->id,
                'batch_ids' => [],
                'rfid_code' => 'RFID-WILD-FULL',
            ]))
            ->assertRedirect();

        $admission = Admission::where('rfid_code', 'RFID-WILD-FULL')->first();
        $this->assertNotNull($admission);
        $this->assertTrue($admission->is_wildcard);
        $this->assertEqualsCanonicalizing(
            [$this->batch->id, $fullBatch->id],
            $admission->slots()->pluck('session_batch_id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(1, $this->batch->fresh()->admission_counter);
        $this->assertSame(2, $fullBatch->fresh()->admission_counter);
    }

    public function test_batch_capacity_is_enforced(): void
    {
        $this->batch->update(['max_size' => 1, 'admission_counter' => 1]);

        $payload = $this->validPayload();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertSessionHasErrors('batch_ids');
    }

    public function test_physically_disabled_is_required_and_stored(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.current'))
            ->assertOk()
            ->assertSee('Physically Disabled?');

        Session::factory()->create(['name' => 'Upcoming Batch']);

        $this->actingAs($this->executive)
            ->get(route('executive.admission.upcoming'))
            ->assertOk()
            ->assertSee('Physically Disabled?');

        $missing = $this->validPayload(['rfid_code' => 'RFID-NO-ANSWER']);
        unset($missing['physically_disabled']);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $missing)
            ->assertSessionHasErrors('physically_disabled');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload([
                'physically_disabled' => 'maybe',
                'rfid_code' => 'RFID-BAD-ANSWER',
            ]))
            ->assertSessionHasErrors('physically_disabled');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload([
                'physically_disabled' => '1',
                'rfid_code' => 'RFID-DISABLED',
            ]))
            ->assertRedirect();

        $this->assertTrue(
            Admission::where('rfid_code', 'RFID-DISABLED')->first()->physically_disabled
        );

        $this->actingAs($this->executive)
            ->post(route('executive.admission.store'), $this->validPayload([
                'physically_disabled' => '0',
                'rfid_code' => 'RFID-NOT-DISABLED',
            ]))
            ->assertRedirect();

        $this->assertFalse(
            Admission::where('rfid_code', 'RFID-NOT-DISABLED')->first()->physically_disabled
        );
    }

    public function test_admission_form_blocks_dates_of_birth_under_one_year(): void
    {
        $max = Admission::latestDateOfBirth();

        $this->actingAs($this->admin)
            ->get(route('admin.admission.current'))
            ->assertOk()
            ->assertSee('max="'.$max.'"', false)
            ->assertSee('Member must be at least 1 year old.');

        $this->actingAs($this->executive)
            ->get(route('executive.admission.current'))
            ->assertOk()
            ->assertSee('max="'.$max.'"', false);
    }

    public function test_date_of_birth_must_be_at_least_one_year_ago(): void
    {
        $tooYoung = [
            'today' => now()->toDateString(),
            'nine months' => now()->subMonths(9)->toDateString(),
            'one day under a year' => now()->subYear()->addDay()->toDateString(),
        ];

        foreach ($tooYoung as $label => $dateOfBirth) {
            $payload = $this->validPayload([
                'date_of_birth' => $dateOfBirth,
                'rfid_code' => 'RFID-YOUNG-'.$label,
            ]);

            $this->actingAs($this->admin)
                ->post(route('admin.admission.store'), $payload)
                ->assertSessionHasErrors([
                    'date_of_birth' => 'Member must be at least 1 year old.',
                ]);
        }

        $this->assertDatabaseMissing('admissions', [
            'full_name' => 'John Doe',
        ]);

        $payload = $this->validPayload([
            'date_of_birth' => now()->subYear()->toDateString(),
            'rfid_code' => 'RFID-ONE-YEAR',
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('admissions', [
            'rfid_code' => 'RFID-ONE-YEAR',
            'date_of_birth' => now()->subYear()->toDateString(),
        ]);
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
                'ages' => [['from_age', 'to_age', 'fee', 'extended_fee']],
                'form_fee',
                'from_date',
                'to_date',
                'extended_date',
            ])
            ->assertJsonPath('from_date', '15 Jan 2026')
            ->assertJsonPath('to_date', '15 Jun 2026')
            ->assertJsonPath('extended_date', '15 Sep 2026')
            ->assertJsonPath('ages.0.extended_fee', 500);
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

    public function test_standard_admission_stores_period_dates_and_fee_breakdown(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload([
                'amount' => 1,
                'session_start_date' => '2000-01-01',
                'session_end_date' => '2000-02-01',
            ]))
            ->assertRedirect();

        $admission = Admission::where('rfid_code', 'RFID001')->first();
        $breakdown = $admission->feeBreakdown;

        $this->assertSame('2026-01-15', $admission->session_start_date->toDateString());
        $this->assertSame('2026-06-15', $admission->session_end_date->toDateString());
        $this->assertFalse($admission->is_extended_membership);
        $this->assertSame('3600.00', $admission->amount);
        $this->assertSame('500.00', $breakdown->membership_amount);
        $this->assertSame('3000.00', $breakdown->age_fee);
        $this->assertSame('100.00', $breakdown->form_fee);
        $this->assertSame('0.00', $breakdown->extended_fee);
        $this->assertSame($this->session->id, $breakdown->session_id);
        $this->assertSame(3600.0, (float) $breakdown->membership_amount + (float) $breakdown->age_fee + (float) $breakdown->form_fee + (float) $breakdown->extended_fee);
        $this->assertSame('3600.00', CashTransaction::find($admission->cash_transaction_id)->amount);
    }

    public function test_extended_membership_uses_extended_date_and_extended_fee_only(): void
    {
        $this->age->update([
            'from_age' => 5,
            'to_age' => 10,
            'fee' => 2000,
            'extended_fee' => 400,
            'extended_fee_late' => 999,
        ]);
        SessionAge::factory()->create([
            'session_id' => $this->session->id,
            'from_age' => 11,
            'to_age' => 20,
            'fee' => 2500,
            'extended_fee' => 900,
            'extended_fee_late' => 50,
        ]);

        $bank = User::factory()->bank()->create(['name' => 'State Bank']);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload([
                'date_of_birth' => now()->subYears(8)->toDateString(),
                'is_extended_membership' => '1',
                'payment_mode' => 'bank',
                'bank_id' => $bank->id,
                'transaction_id' => 'EXT-100',
                'amount' => 99,
                'session_end_date' => '2030-01-01',
            ]))
            ->assertRedirect();

        $admission = Admission::where('rfid_code', 'RFID001')->first();

        $this->assertTrue($admission->is_extended_membership);
        $this->assertSame('2026-01-15', $admission->session_start_date->toDateString());
        $this->assertSame('2026-09-15', $admission->session_end_date->toDateString());
        $this->assertSame('3000.00', $admission->amount);
        $this->assertSame($this->age->id, $admission->session_age_id);
        $this->assertSame('400.00', $admission->feeBreakdown->extended_fee);
        $this->assertSame('2000.00', $admission->feeBreakdown->age_fee);
        $this->assertSame('3000.00', $admission->cashTransaction->amount);
        $this->assertSame($bank->id, $admission->cashTransaction->user_id);

        $this->session->update(['form_fee' => 999]);
        $this->assertSame('100.00', $admission->feeBreakdown->fresh()->form_fee);

        $this->actingAs($this->admin)
            ->get(route('admin.admission.show', $admission))
            ->assertOk()
            ->assertSee('15 Jan 2026')
            ->assertSee('15 Sep 2026')
            ->assertSee('Extended Session')
            ->assertSee('Extended Fee')
            ->assertSee('State Bank')
            ->assertSee('EXT-100')
            ->assertSee($this->membership->card_name);

        $this->actingAs($this->admin)
            ->get(route('admin.admission.edit', $admission))
            ->assertOk()
            ->assertSee('Extended Fee')
            ->assertSee('State Bank')
            ->assertSee('EXT-100')
            ->assertSee('Payment Mode')
            ->assertDontSee('<select name="is_extended_membership"', false)
            ->assertDontSee('name="date_of_birth"', false)
            ->assertDontSee('name="amount"', false);
    }

    public function test_extended_session_choice_is_required(): void
    {
        $payload = $this->validPayload();
        unset($payload['is_extended_membership']);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $payload)
            ->assertSessionHasErrors('is_extended_membership');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload([
                'is_extended_membership' => 'maybe',
                'rfid_code' => 'RFID-MAYBE',
            ]))
            ->assertSessionHasErrors('is_extended_membership');
    }

    public function test_session_without_period_dates_cannot_be_used(): void
    {
        $this->session->update([
            'from_date' => null,
            'to_date' => null,
            'extended_date' => null,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload())
            ->assertSessionHasErrors('session_id');

        $this->assertDatabaseCount('admissions', 0);
        $this->assertDatabaseCount('fee_breakdowns', 0);
    }

    public function test_duplicate_batch_ids_do_not_satisfy_the_slot_count(): void
    {
        $twoSlot = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Two Slot',
            'no_of_slot' => 2,
            'membership_cost' => 800,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.store'), $this->validPayload([
                'session_membership_id' => $twoSlot->id,
                'batch_ids' => [$this->batch->id, $this->batch->id],
            ]))
            ->assertSessionHasErrors('batch_ids');

        $this->assertDatabaseCount('admissions', 0);
    }

    public function test_calculate_fee_returns_saved_breakdown_rules(): void
    {
        $query = [
            'session_id' => $this->session->id,
            'session_membership_id' => $this->membership->id,
            'date_of_birth' => now()->subYears(15)->toDateString(),
        ];

        $this->actingAs($this->admin)
            ->getJson(route('admin.admission.calculate-fee', $query + ['is_extended_membership' => 0]))
            ->assertOk()
            ->assertJsonPath('amount', 3600)
            ->assertJsonPath('extended_fee', 0)
            ->assertJsonPath('is_extended', false)
            ->assertJsonPath('form_fee', 100)
            ->assertJsonPath('age_fee', 3000)
            ->assertJsonPath('membership_amount', 500);

        $this->actingAs($this->executive)
            ->getJson(route('executive.admission.calculate-fee', $query + ['is_extended_membership' => 1]))
            ->assertOk()
            ->assertJsonPath('amount', 4100)
            ->assertJsonPath('extended_fee', 500)
            ->assertJsonPath('is_extended', true)
            ->assertJsonPath('age_label', '1–150 years');

        $this->age->update(['from_age' => 30, 'to_age' => 40]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.admission.calculate-fee', $query + ['is_extended_membership' => 1]))
            ->assertOk()
            ->assertJsonPath('amount', 0)
            ->assertJsonPath('error', 'No age group found for the member\'s age (15 years).');
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
            'physically_disabled' => '0',
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
            'is_extended_membership' => '0',
            'payment_mode' => 'cash',
        ], $overrides);
    }
}
