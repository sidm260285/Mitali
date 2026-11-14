<?php

namespace Tests\Feature;

use App\Models\AccountHead;
use App\Models\Admission;
use App\Models\AdmissionSlot;
use App\Models\CashTransaction;
use App\Models\FeeBreakdown;
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

        $this->admission = Admission::create($this->admissionAttributes());

        AdmissionSlot::create([
            'admission_id' => $this->admission->id,
            'session_batch_id' => $this->batch->id,
        ]);

        FeeBreakdown::create([
            'session_id' => $this->session->id,
            'admission_id' => $this->admission->id,
            'membership_amount' => 500,
            'age_fee' => 3000,
            'form_fee' => 100,
            'extended_fee' => 0,
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
            ->assertSee('Admission Details')
            ->assertSeeInOrder(['Physically Disabled?', 'No'])
            ->assertSee('Session Start Date')
            ->assertSee('15 Jan 2026')
            ->assertSee('15 Jun 2026')
            ->assertSee('Extended Session')
            ->assertSee('Age Fee (1–150 years)')
            ->assertSee('Form Fee')
            ->assertDontSee('Extended Fee')
            ->assertDontSee('Ref Transaction ID');
    }

    public function test_admission_list_does_not_show_physically_disabled(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.list', ['session_id' => $this->session->id]))
            ->assertOk()
            ->assertSee('Test Member')
            ->assertDontSee('Physically Disabled?')
            ->assertDontSee('Session Start Date')
            ->assertDontSee('Extended Fee');
    }

    public function test_admin_and_executive_can_update_physically_disabled(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.admission.edit', $this->admission))
            ->assertOk()
            ->assertSee('Physically Disabled?');

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $this->editPayload([
                'physically_disabled' => '1',
            ]))
            ->assertRedirect();

        $this->assertTrue($this->admission->fresh()->physically_disabled);

        $this->actingAs($this->executive)
            ->get(route('executive.admission.show', $this->admission))
            ->assertOk()
            ->assertSee('Yes');

        $this->actingAs($this->executive)
            ->put(route('executive.admission.update', $this->admission), $this->editPayload([
                'physically_disabled' => '0',
            ]))
            ->assertRedirect();

        $this->assertFalse($this->admission->fresh()->physically_disabled);
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
            ->assertSee('Edit Admission')
            ->assertSee('Session Start Date')
            ->assertSee('15 Jan 2026')
            ->assertSee('Extended Session')
            ->assertSee('Age Fee (1–150 years)')
            ->assertSee($this->admission->date_of_birth->format('d M Y'))
            ->assertDontSee('name="date_of_birth"', false)
            ->assertSee('id="upgradeForm"', false)
            ->assertSee('id="upgradeSubmit"', false);
    }

    public function test_edit_ignores_date_of_birth_changes(): void
    {
        $original = $this->admission->date_of_birth->toDateString();

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $this->editPayload([
                'date_of_birth' => now()->subMonths(9)->toDateString(),
            ]))
            ->assertRedirect();

        $this->actingAs($this->executive)
            ->put(route('executive.admission.update', $this->admission), $this->editPayload([
                'date_of_birth' => now()->subYear()->toDateString(),
            ]))
            ->assertRedirect();

        $this->assertSame($original, $this->admission->fresh()->date_of_birth->toDateString());
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
        Admission::create($this->admissionAttributes([
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
        ]));

        $payload = $this->editPayload(['rfid_code' => 'RFID-TAKEN']);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $payload)
            ->assertSessionHasErrors('rfid_code');
    }

    public function test_membership_cannot_be_changed_and_slot_count_stays_fixed(): void
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

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $this->editPayload([
                'session_membership_id' => $newMembership->id,
                'batch_ids' => [$this->batch->id, $batch2->id],
                'is_extended_membership' => '1',
                'amount' => 1,
                'session_end_date' => '2030-01-01',
            ]))
            ->assertSessionHasErrors('batch_ids');

        $breakdownId = $this->admission->feeBreakdown->id;

        $fresh = $this->admission->fresh();
        $this->assertSame($this->membership->id, $fresh->session_membership_id);
        $this->assertSame($this->batch->id, (int) $fresh->slots()->value('session_batch_id'));
        $this->assertFalse($fresh->is_extended_membership);
        $this->assertSame('3600.00', $fresh->amount);
        $this->assertSame('2026-06-15', $fresh->session_end_date->toDateString());
        $this->assertSame($breakdownId, $fresh->feeBreakdown->id);
    }

    public function test_batch_can_be_swapped_with_attendance_without_changing_the_charge(): void
    {
        $this->admission->update(['attendance_count' => 4]);
        $this->batch->update(['admission_counter' => 1, 'max_size' => 1]);

        $batch2 = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
            'max_size' => 10,
            'admission_counter' => 2,
        ]);

        $transaction = CashTransaction::create([
            'user_id' => $this->admin->id,
            'account_head_id' => AccountHead::findSystem(AccountHead::SYSTEM_ADMISSION)->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_CASH,
            'amount' => 3600,
            'transaction_date' => now()->toDateString(),
            'narration' => 'Admission: Test Member',
        ]);
        $this->admission->update(['cash_transaction_id' => $transaction->id]);

        $this->actingAs($this->executive)
            ->put(route('executive.admission.update', $this->admission), $this->editPayload([
                'batch_ids' => [$batch2->id],
                'session_membership_id' => 999999,
            ]))
            ->assertRedirect();

        $fresh = $this->admission->fresh();
        $this->assertSame($batch2->id, (int) $fresh->slots()->value('session_batch_id'));
        $this->assertSame(0, $this->batch->fresh()->admission_counter);
        $this->assertSame(3, $batch2->fresh()->admission_counter);
        $this->assertSame($this->membership->id, $fresh->session_membership_id);
        $this->assertSame('3600.00', $fresh->amount);
        $this->assertSame('3600.00', $transaction->fresh()->amount);
        $this->assertSame('100.00', $fresh->feeBreakdown->form_fee);
        $this->assertSame(4, $fresh->attendance_count);
    }

    public function test_swap_into_a_full_batch_is_rejected_but_the_current_full_batch_can_be_kept(): void
    {
        $this->batch->update(['max_size' => 1, 'admission_counter' => 1]);
        $fullBatch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'max_size' => 1,
            'admission_counter' => 1,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $this->editPayload([
                'batch_ids' => [$fullBatch->id],
            ]))
            ->assertSessionHasErrors('batch_ids');

        $this->assertSame($this->batch->id, (int) $this->admission->fresh()->slots()->value('session_batch_id'));
        $this->assertSame(1, $this->batch->fresh()->admission_counter);
        $this->assertSame(1, $fullBatch->fresh()->admission_counter);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $this->editPayload([
                'batch_ids' => [$this->batch->id, $this->batch->id],
            ]))
            ->assertRedirect();

        $this->assertSame($this->batch->id, (int) $this->admission->fresh()->slots()->value('session_batch_id'));
        $this->assertSame(1, $this->batch->fresh()->admission_counter);
    }

    public function test_batch_from_another_session_is_rejected(): void
    {
        $foreignBatch = SessionBatch::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $this->admission), $this->editPayload([
                'batch_ids' => [$foreignBatch->id],
            ]))
            ->assertSessionHasErrors('batch_ids');

        $this->assertSame($this->batch->id, (int) $this->admission->fresh()->slots()->value('session_batch_id'));
    }

    public function test_wildcard_slots_stay_unchanged_when_other_details_are_edited(): void
    {
        $wildcard = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Any Access',
            'no_of_slot' => -1,
            'membership_cost' => 1000,
        ]);
        $batch2 = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
            'admission_counter' => 1,
        ]);
        $fullBatch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_size' => 1,
            'admission_counter' => 1,
        ]);

        $admission = Admission::create($this->admissionAttributes([
            'session_membership_id' => $wildcard->id,
            'full_name' => 'Wildcard Member',
            'rfid_code' => 'RFID-WILD',
            'is_wildcard' => true,
            'mobile_no' => '9876543210',
        ]));
        AdmissionSlot::create(['admission_id' => $admission->id, 'session_batch_id' => $this->batch->id]);
        AdmissionSlot::create(['admission_id' => $admission->id, 'session_batch_id' => $batch2->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.admission.update', $admission), $this->editPayload([
                'full_name' => 'Wildcard Member',
                'rfid_code' => 'RFID-WILD',
                'mobile_no' => '9123456780',
                'batch_ids' => [$this->batch->id],
            ]))
            ->assertRedirect();

        $slotIds = $admission->slots()->pluck('session_batch_id')->map(fn ($id) => (int) $id)->sort()->values()->all();
        $expectedSlots = collect([$this->batch->id, $batch2->id])->sort()->values()->all();
        $this->assertSame($expectedSlots, $slotIds);
        $this->assertNull($admission->slots()->where('session_batch_id', $fullBatch->id)->first());
        $this->assertSame('9123456780', $admission->fresh()->mobile_no);
        $this->assertSame(1, $fullBatch->fresh()->admission_counter);
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

    private function admissionAttributes(array $overrides = []): array
    {
        return array_merge([
            'session_id' => $this->session->id,
            'session_start_date' => '2026-01-15',
            'session_end_date' => '2026-06-15',
            'is_extended_membership' => false,
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
        ], $overrides);
    }

    private function editPayload(array $overrides = []): array
    {
        return array_merge([
            'full_name' => $this->admission->full_name,
            'physically_disabled' => $this->admission->physically_disabled ? '1' : '0',
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
