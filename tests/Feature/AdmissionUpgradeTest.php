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
use Carbon\Carbon;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdmissionUpgradeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $executive;

    private Session $session;

    private SessionAge $age;

    private SessionBatch $batch;

    private SessionBatch $secondBatch;

    private SessionMembership $membership;

    private SessionMembership $platinum;

    private Admission $admission;

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
            'admission_count' => 7,
        ]);
        $this->age = SessionAge::factory()->create([
            'session_id' => $this->session->id,
            'from_age' => 1,
            'to_age' => 150,
            'fee' => 3000,
            'extended_fee' => 400,
            'extended_fee_late' => 900,
            'admission_counter' => 3,
        ]);
        $this->batch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'max_size' => 30,
            'admission_counter' => 4,
        ]);
        $this->secondBatch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '07:00:00',
            'end_time' => '08:00:00',
            'max_size' => 10,
            'admission_counter' => 0,
        ]);
        $this->membership = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Standard',
            'no_of_slot' => 1,
            'membership_cost' => 500,
            'admission_counter' => 2,
        ]);
        $this->platinum = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Platinum',
            'no_of_slot' => 2,
            'membership_cost' => 1500,
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

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_edit_page_shows_current_choice_and_a_disabled_upgrade_button(): void
    {
        SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Budget Card',
            'no_of_slot' => 3,
            'membership_cost' => 100,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.admission.edit', $this->admission))
            ->assertOk()
            ->assertSee('Upgrade')
            ->assertSee('Current membership: Standard (1 Slot(s))')
            ->assertSee('Platinum (2 Slot(s), ₹1,500.00)')
            ->assertSee('— Current', false)
            ->assertDontSee('Budget Card')
            ->assertSee('id="upgrade_amount" class="form-control" value="0.00" readonly', false)
            ->assertSee('id="upgradeSubmit" class="btn btn-form btn-form-primary" disabled', false)
            ->assertSee('Within '.(int) config('admission.extended_upgrade_grace_days').' days of admission');

        $this->actingAs($this->executive)
            ->get(route('executive.admission.edit', $this->admission))
            ->assertOk()
            ->assertSee('Current membership: Standard (1 Slot(s))');
    }

    public function test_edit_page_offers_only_higher_cards_with_enough_slots_or_wildcard(): void
    {
        $wide = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Wide Current',
            'no_of_slot' => 3,
            'membership_cost' => 800,
        ]);
        $this->admission->update(['session_membership_id' => $wide->id]);

        SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'All Day Card',
            'no_of_slot' => -1,
            'membership_cost' => 2500,
        ]);
        SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Roomy Card',
            'no_of_slot' => 4,
            'membership_cost' => 2000,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.admission.edit', $this->admission))
            ->assertOk()
            ->assertSee('Current membership: Wide Current (3 Slot(s))')
            ->assertSee('All Day Card')
            ->assertSee('Roomy Card')
            ->assertDontSee('Platinum (2 Slot(s)')
            ->assertDontSee('Standard (1 Slot(s), ₹500.00)');
    }

    public function test_membership_upgrade_charges_the_gap_and_keeps_the_original_payment(): void
    {
        $original = $this->originalPayment();
        $balanceBefore = $this->admin->freshBalance();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload())
            ->assertRedirect(route('admin.admission.edit', $this->admission))
            ->assertSessionHas('success');

        $fresh = $this->admission->fresh();
        $breakdown = $fresh->feeBreakdown;
        $upgrade = CashTransaction::query()->where('narration', 'Admission upgrade: Test Member')->first();

        $this->assertSame($this->platinum->id, $fresh->session_membership_id);
        $this->assertFalse($fresh->is_wildcard);
        $this->assertFalse($fresh->is_extended_membership);
        $this->assertSame('4600.00', $fresh->amount);
        $this->assertSame($original->id, $fresh->cash_transaction_id);
        $this->assertSame('3600.00', $original->fresh()->amount);
        $this->assertSame('1500.00', $breakdown->membership_amount);
        $this->assertSame('3000.00', $breakdown->age_fee);
        $this->assertSame('100.00', $breakdown->form_fee);
        $this->assertSame('0.00', $breakdown->extended_fee);
        $this->assertSame($this->session->id, $breakdown->session_id);
        $this->assertEqualsCanonicalizing(
            [$this->batch->id, $this->secondBatch->id],
            $fresh->slots()->pluck('session_batch_id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(1, $this->membership->fresh()->admission_counter);
        $this->assertSame(1, $this->platinum->fresh()->admission_counter);
        $this->assertSame(4, $this->batch->fresh()->admission_counter);
        $this->assertSame(1, $this->secondBatch->fresh()->admission_counter);
        $this->assertSame(7, $this->session->fresh()->admission_count);
        $this->assertSame(3, $this->age->fresh()->admission_counter);
        $this->assertNotNull($upgrade);
        $this->assertSame(CashTransaction::TYPE_CREDIT, $upgrade->type);
        $this->assertSame(CashTransaction::MODE_CASH, $upgrade->mode);
        $this->assertSame('1000.00', $upgrade->amount);
        $this->assertSame($this->admin->id, $upgrade->user_id);
        $this->assertSame($this->admin->id, (int) $upgrade->entry_by);
        $this->assertSame(AccountHead::findSystem(AccountHead::SYSTEM_ADMISSION)->id, $upgrade->account_head_id);
        $this->assertSame(1, CashTransaction::query()->where('narration', 'like', 'Admission upgrade:%')->count());
        $this->assertEquals($balanceBefore + 1000, $this->admin->freshBalance());
    }

    public function test_membership_and_extended_session_share_one_cash_row(): void
    {
        $this->actingAs($this->executive)
            ->post(route('executive.admission.upgrade', $this->admission), $this->upgradePayload([
                'is_extended_membership' => '1',
            ]))
            ->assertRedirect(route('executive.admission.edit', $this->admission));

        $fresh = $this->admission->fresh();
        $upgrade = CashTransaction::query()->where('narration', 'Admission upgrade: Test Member')->first();

        $this->assertSame($this->platinum->id, $fresh->session_membership_id);
        $this->assertTrue($fresh->is_extended_membership);
        $this->assertSame('2026-09-15', $fresh->session_end_date->toDateString());
        $this->assertSame('5000.00', $fresh->amount);
        $this->assertSame('1500.00', $fresh->feeBreakdown->membership_amount);
        $this->assertSame('400.00', $fresh->feeBreakdown->extended_fee);
        $this->assertSame('3000.00', $fresh->feeBreakdown->age_fee);
        $this->assertSame('100.00', $fresh->feeBreakdown->form_fee);
        $this->assertNotNull($upgrade);
        $this->assertSame('1400.00', $upgrade->amount);
        $this->assertSame($this->executive->id, $upgrade->user_id);
        $this->assertSame(1, CashTransaction::query()->where('narration', 'like', 'Admission upgrade:%')->count());
        $this->assertSame(7, $this->session->fresh()->admission_count);

        $this->actingAs($this->executive)
            ->get(route('executive.admission.show', $fresh))
            ->assertOk()
            ->assertSee('Platinum')
            ->assertSee('15 Sep 2026')
            ->assertSee('5,000.00')
            ->assertSee('Extended Fee');
    }

    public function test_bank_upgrade_credits_the_selected_bank(): void
    {
        $bank = User::factory()->bank()->create(['name' => 'State Bank']);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'payment_mode' => 'bank',
                'bank_id' => $bank->id,
                'transaction_id' => 'UPG-100',
            ]))
            ->assertRedirect();

        $upgrade = CashTransaction::query()->where('narration', 'Admission upgrade: Test Member')->first();
        $this->assertSame($bank->id, $upgrade->user_id);
        $this->assertSame(CashTransaction::MODE_BANK, $upgrade->mode);
        $this->assertSame('UPG-100', $upgrade->transaction_id);
        $this->assertSame($this->admin->id, (int) $upgrade->entry_by);
        $this->assertSame('1000.00', $upgrade->amount);
        $this->assertEquals(1000, $bank->freshBalance());
        $this->assertEquals(0, $this->admin->freshBalance());
    }

    public function test_extended_fee_uses_the_on_time_amount_through_the_grace_day(): void
    {
        Carbon::setTestNow('2026-11-14 12:00:00');
        $grace = (int) config('admission.extended_upgrade_grace_days');
        $this->admission->forceFill(['created_at' => now()->subDays($grace)->setTime(23, 30)])->save();

        $this->actingAs($this->admin)
            ->getJson(route('admin.admission.upgrade-quote', [
                'admission' => $this->admission,
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => 1,
            ]))
            ->assertOk()
            ->assertJsonPath('amount', 400)
            ->assertJsonPath('extended_fee', 400)
            ->assertJsonPath('is_late', false);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => '1',
            ]))
            ->assertRedirect();

        $fresh = $this->admission->fresh();
        $this->assertTrue($fresh->is_extended_membership);
        $this->assertSame('4000.00', $fresh->amount);
        $this->assertSame('400.00', $fresh->feeBreakdown->extended_fee);
        $this->assertSame('500.00', $fresh->feeBreakdown->membership_amount);
        $this->assertSame($this->membership->id, $fresh->session_membership_id);
        $this->assertSame('2026-09-15', $fresh->session_end_date->toDateString());
    }

    public function test_extended_fee_uses_the_late_amount_after_the_grace_day(): void
    {
        Carbon::setTestNow('2026-11-14 12:00:00');
        $grace = (int) config('admission.extended_upgrade_grace_days');
        $this->admission->forceFill(['created_at' => now()->subDays($grace + 1)])->save();

        $this->actingAs($this->admin)
            ->getJson(route('admin.admission.upgrade-quote', [
                'admission' => $this->admission,
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => 1,
            ]))
            ->assertOk()
            ->assertJsonPath('amount', 900)
            ->assertJsonPath('is_late', true)
            ->assertJsonPath('extended_label', 'Extended fee (late)');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => '1',
            ]))
            ->assertRedirect();

        $fresh = $this->admission->fresh();
        $this->assertSame('4500.00', $fresh->amount);
        $this->assertSame('900.00', $fresh->feeBreakdown->extended_fee);
        $this->assertSame('900.00', CashTransaction::query()->where('narration', 'like', 'Admission upgrade:%')->value('amount'));
    }

    public function test_grace_days_come_from_configuration(): void
    {
        Carbon::setTestNow('2026-11-14 12:00:00');
        config(['admission.extended_upgrade_grace_days' => 10]);
        $this->admission->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->actingAs($this->admin)
            ->get(route('admin.admission.edit', $this->admission))
            ->assertOk()
            ->assertSee('Within 10 days of admission');

        $this->actingAs($this->admin)
            ->getJson(route('admin.admission.upgrade-quote', [
                'admission' => $this->admission,
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => 1,
            ]))
            ->assertJsonPath('extended_fee', 400)
            ->assertJsonPath('is_late', false);

        $this->admission->forceFill(['created_at' => now()->subDays(11)])->save();

        $this->actingAs($this->admin)
            ->getJson(route('admin.admission.upgrade-quote', [
                'admission' => $this->admission,
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => 1,
            ]))
            ->assertJsonPath('extended_fee', 900)
            ->assertJsonPath('is_late', true);
    }

    public function test_ineligible_membership_changes_are_rejected(): void
    {
        $cheaper = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Budget',
            'no_of_slot' => 4,
            'membership_cost' => 100,
        ]);
        $samePrice = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Same Price',
            'no_of_slot' => 3,
            'membership_cost' => 500,
        ]);

        foreach ([$cheaper, $samePrice] as $membership) {
            $this->actingAs($this->admin)
                ->from(route('admin.admission.edit', $this->admission))
                ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                    'session_membership_id' => $membership->id,
                ]))
                ->assertRedirect(route('admin.admission.edit', $this->admission))
                ->assertSessionHasErrors('session_membership_id', null, 'admissionUpgrade');
        }

        $samePrice->update(['membership_cost' => 800, 'card_name' => 'Wide Current']);
        $this->admission->update(['session_membership_id' => $samePrice->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.admission.edit', $this->admission))
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload())
            ->assertRedirect(route('admin.admission.edit', $this->admission))
            ->assertSessionHasErrors('session_membership_id', null, 'admissionUpgrade');

        $this->assertSame($samePrice->id, $this->admission->fresh()->session_membership_id);
        $this->assertSame(0, CashTransaction::query()->count());
    }

    public function test_upgrade_without_a_change_or_payment_does_not_write(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.admission.edit', $this->admission))
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => '0',
            ]))
            ->assertSessionHasErrors('upgrade', null, 'admissionUpgrade');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'payment_mode' => null,
            ]))
            ->assertSessionHasErrors('payment_mode', null, 'admissionUpgrade');

        $inactiveBank = User::factory()->bank()->inactive()->create();
        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'payment_mode' => 'bank',
                'bank_id' => $inactiveBank->id,
                'transaction_id' => 'UPG-INACTIVE',
            ]))
            ->assertSessionHasErrors('bank_id', null, 'admissionUpgrade');

        $this->assertSame($this->membership->id, $this->admission->fresh()->session_membership_id);
        $this->assertSame('3600.00', $this->admission->fresh()->amount);
        $this->assertSame(0, CashTransaction::query()->count());
        $this->assertSame(2, $this->membership->fresh()->admission_counter);
    }

    public function test_more_slots_require_an_exact_open_batch_selection(): void
    {
        $fullBatch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_size' => 1,
            'admission_counter' => 1,
        ]);
        $foreignBatch = SessionBatch::factory()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'batch_ids' => [$this->batch->id, $this->batch->id],
            ]))
            ->assertSessionHasErrors('batch_ids', null, 'admissionUpgrade');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'batch_ids' => [$this->batch->id, $foreignBatch->id],
            ]))
            ->assertSessionHasErrors('batch_ids', null, 'admissionUpgrade');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'batch_ids' => [$this->batch->id, $fullBatch->id],
            ]))
            ->assertSessionHasErrors('batch_ids', null, 'admissionUpgrade');

        $this->assertSame($this->membership->id, $this->admission->fresh()->session_membership_id);
        $this->assertSame(1, $fullBatch->fresh()->admission_counter);
        $this->assertSame(0, CashTransaction::query()->count());

        $this->batch->update(['max_size' => 1, 'admission_counter' => 1]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'batch_ids' => [$this->batch->id, $this->secondBatch->id],
            ]))
            ->assertRedirect();

        $fresh = $this->admission->fresh();
        $this->assertSame($this->platinum->id, $fresh->session_membership_id);
        $this->assertEqualsCanonicalizing(
            [$this->batch->id, $this->secondBatch->id],
            $fresh->slots()->pluck('session_batch_id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(1, $this->batch->fresh()->admission_counter);
        $this->assertSame(1, $this->secondBatch->fresh()->admission_counter);
        $this->assertSame('4600.00', $fresh->amount);
    }

    public function test_wildcard_upgrade_includes_full_batches(): void
    {
        $wildcard = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'All Access',
            'no_of_slot' => -1,
            'membership_cost' => 2500,
        ]);
        $fullBatch = SessionBatch::factory()->create([
            'session_id' => $this->session->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'max_size' => 1,
            'admission_counter' => 1,
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'session_membership_id' => $wildcard->id,
                'batch_ids' => [],
            ]))
            ->assertRedirect();

        $fresh = $this->admission->fresh();
        $this->assertTrue($fresh->is_wildcard);
        $this->assertSame($wildcard->id, $fresh->session_membership_id);
        $this->assertSame('5600.00', $fresh->amount);
        $this->assertSame('2500.00', $fresh->feeBreakdown->membership_amount);
        $this->assertEqualsCanonicalizing(
            [$this->batch->id, $this->secondBatch->id, $fullBatch->id],
            $fresh->slots()->pluck('session_batch_id')->map(fn ($id) => (int) $id)->all(),
        );
        $this->assertSame(4, $this->batch->fresh()->admission_counter);
        $this->assertSame(1, $this->secondBatch->fresh()->admission_counter);
        $this->assertSame(2, $fullBatch->fresh()->admission_counter);
        $this->assertSame(1, $this->membership->fresh()->admission_counter);
        $this->assertSame(1, $wildcard->fresh()->admission_counter);
    }

    public function test_wildcard_admission_can_extend_but_cannot_change_card(): void
    {
        $wildcard = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'All Access',
            'no_of_slot' => -1,
            'membership_cost' => 2500,
        ]);
        $higher = SessionMembership::factory()->create([
            'session_id' => $this->session->id,
            'card_name' => 'Should Not List',
            'no_of_slot' => 4,
            'membership_cost' => 3000,
        ]);
        $this->admission->update([
            'session_membership_id' => $wildcard->id,
            'is_wildcard' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.admission.edit', $this->admission))
            ->assertOk()
            ->assertSee('Current membership: All Access (Any Slot)')
            ->assertDontSee('Should Not List')
            ->assertDontSee('<select name="session_membership_id"', false);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'session_membership_id' => $higher->id,
            ]))
            ->assertSessionHasErrors('session_membership_id', null, 'admissionUpgrade');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'session_membership_id' => $wildcard->id,
                'is_extended_membership' => '1',
            ]))
            ->assertRedirect();

        $fresh = $this->admission->fresh();
        $this->assertTrue($fresh->is_wildcard);
        $this->assertSame($wildcard->id, $fresh->session_membership_id);
        $this->assertTrue($fresh->is_extended_membership);
        $this->assertSame('4000.00', $fresh->amount);
        $this->assertSame('400.00', $fresh->feeBreakdown->extended_fee);
        $this->assertSame($this->batch->id, (int) $fresh->slots()->value('session_batch_id'));
    }

    public function test_an_extended_admission_cannot_be_turned_off_and_is_not_charged_again(): void
    {
        $this->admission->update([
            'is_extended_membership' => true,
            'session_end_date' => '2026-09-15',
            'amount' => 4000,
        ]);
        $this->admission->feeBreakdown->update(['extended_fee' => 400]);

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'session_membership_id' => $this->membership->id,
                'is_extended_membership' => '0',
            ]))
            ->assertSessionHasErrors('is_extended_membership', null, 'admissionUpgrade');

        $this->actingAs($this->admin)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload([
                'is_extended_membership' => '1',
            ]))
            ->assertRedirect();

        $fresh = $this->admission->fresh();
        $this->assertTrue($fresh->is_extended_membership);
        $this->assertSame('5000.00', $fresh->amount);
        $this->assertSame('400.00', $fresh->feeBreakdown->extended_fee);
        $this->assertSame('1500.00', $fresh->feeBreakdown->membership_amount);
        $this->assertSame('1000.00', CashTransaction::query()->where('narration', 'like', 'Admission upgrade:%')->value('amount'));
        $this->assertSame('2026-09-15', $fresh->session_end_date->toDateString());
    }

    public function test_bank_user_cannot_upgrade_an_admission(): void
    {
        $bank = User::factory()->bank()->create();

        $this->actingAs($bank)
            ->get(route('admin.admission.edit', $this->admission))
            ->assertForbidden();

        $this->actingAs($bank)
            ->post(route('admin.admission.upgrade', $this->admission), $this->upgradePayload())
            ->assertForbidden();
    }

    public function test_a_new_batch_includes_existing_wildcard_admissions_without_blocking_when_full(): void
    {
        $syncSession = Session::factory()->create([
            'name' => 'Wildcard Sync Session',
            'status' => Session::STATUS_UPCOMING,
            'from_date' => '2026-01-15',
            'to_date' => '2026-06-15',
            'extended_date' => '2026-09-15',
            'form_fee' => 50,
        ]);
        $age = SessionAge::factory()->create([
            'session_id' => $syncSession->id,
            'from_age' => 1,
            'to_age' => 150,
            'fee' => 1000,
            'extended_fee' => 100,
            'extended_fee_late' => 50,
        ]);
        $existingBatch = SessionBatch::factory()->create([
            'session_id' => $syncSession->id,
            'start_time' => '05:20:00',
            'end_time' => '06:00:00',
            'buffer_time' => 10,
            'max_size' => 30,
            'admission_counter' => 3,
        ]);
        $normalCard = SessionMembership::factory()->create([
            'session_id' => $syncSession->id,
            'card_name' => 'Single',
            'no_of_slot' => 1,
            'membership_cost' => 200,
        ]);
        $wildcardCard = SessionMembership::factory()->create([
            'session_id' => $syncSession->id,
            'card_name' => 'Any',
            'no_of_slot' => -1,
            'membership_cost' => 800,
        ]);

        $firstWildcard = $this->makeSyncedAdmission($syncSession, $age, $wildcardCard, 'RFID-SYNC-1', true);
        $secondWildcard = $this->makeSyncedAdmission($syncSession, $age, $wildcardCard, 'RFID-SYNC-2', true);
        $normal = $this->makeSyncedAdmission($syncSession, $age, $normalCard, 'RFID-SYNC-3', false);
        foreach ([$firstWildcard, $secondWildcard, $normal] as $admission) {
            AdmissionSlot::create([
                'admission_id' => $admission->id,
                'session_batch_id' => $existingBatch->id,
            ]);
        }

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $syncSession), $this->sessionPayload($syncSession, $age, $existingBatch, $normalCard, $wildcardCard))
            ->assertRedirect();

        $newBatch = SessionBatch::query()
            ->where('session_id', $syncSession->id)
            ->where('start_time', '07:00:00')
            ->first();

        $this->assertNotNull($newBatch);
        $this->assertSame(2, $newBatch->admission_counter);
        $this->assertSame(1, $newBatch->max_size);
        $this->assertSame(3, $existingBatch->fresh()->admission_counter);
        $this->assertNotNull($firstWildcard->slots()->where('session_batch_id', $newBatch->id)->first());
        $this->assertNotNull($secondWildcard->slots()->where('session_batch_id', $newBatch->id)->first());
        $this->assertNull($normal->slots()->where('session_batch_id', $newBatch->id)->first());
        $this->assertSame(2, $firstWildcard->slots()->count());

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $syncSession), $this->sessionPayload($syncSession, $age, $existingBatch, $normalCard, $wildcardCard, $newBatch))
            ->assertRedirect();

        $this->assertSame(2, $newBatch->fresh()->admission_counter);
        $this->assertSame(2, $firstWildcard->slots()->count());
        $this->assertSame(3, $existingBatch->fresh()->admission_counter);

        $this->actingAs($this->admin)
            ->put(route('admin.sessions.update', $syncSession), $this->sessionPayload($syncSession, $age, $existingBatch, $normalCard, $wildcardCard, $newBatch, 5))
            ->assertSessionHasErrors('batches');

        $this->assertSame(2, $newBatch->fresh()->admission_counter);
        $this->assertSame(1, $newBatch->fresh()->max_size);
        $this->assertSame(2, $firstWildcard->fresh()->slots()->count());
    }

    private function originalPayment(): CashTransaction
    {
        $original = CashTransaction::create([
            'user_id' => $this->admin->id,
            'account_head_id' => AccountHead::findSystem(AccountHead::SYSTEM_ADMISSION)->id,
            'type' => CashTransaction::TYPE_CREDIT,
            'mode' => CashTransaction::MODE_CASH,
            'amount' => 3600,
            'transaction_date' => now()->toDateString(),
            'narration' => 'Admission: Test Member',
            'entry_by' => $this->admin->id,
        ]);
        $this->admission->update(['cash_transaction_id' => $original->id]);

        return $original;
    }

    private function upgradePayload(array $overrides = []): array
    {
        return array_merge([
            'session_membership_id' => $this->platinum->id,
            'is_extended_membership' => '0',
            'batch_ids' => [$this->batch->id, $this->secondBatch->id],
            'payment_mode' => 'cash',
        ], $overrides);
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

    private function makeSyncedAdmission(Session $session, SessionAge $age, SessionMembership $membership, string $rfid, bool $wildcard): Admission
    {
        return Admission::create([
            'session_id' => $session->id,
            'session_start_date' => '2026-01-15',
            'session_end_date' => '2026-06-15',
            'is_extended_membership' => false,
            'session_membership_id' => $membership->id,
            'session_age_id' => $age->id,
            'full_name' => $rfid,
            'gender' => 'male',
            'date_of_birth' => '2010-01-01',
            'mobile_no' => '9876543210',
            'guardian_name' => 'Guardian',
            'relation' => 'father',
            'emergency_contact_no' => '9876543211',
            'address' => 'Address',
            'police_station' => 'PS',
            'pin_code' => '700001',
            'rfid_code' => $rfid,
            'is_wildcard' => $wildcard,
            'amount' => 100,
            'payment_mode' => 'cash',
            'admitted_by' => $this->admin->id,
        ]);
    }

    private function sessionPayload(
        Session $session,
        SessionAge $age,
        SessionBatch $existingBatch,
        SessionMembership $normalCard,
        SessionMembership $wildcardCard,
        ?SessionBatch $newBatch = null,
        ?int $newBatchMaxSize = null,
    ): array {
        $batches = [[
            'id' => $existingBatch->id,
            'start_hour' => 5,
            'start_minute' => 20,
            'start_period' => 'AM',
            'end_hour' => 6,
            'end_minute' => 0,
            'end_period' => 'AM',
            'buffer_time' => 10,
            'max_size' => 30,
        ]];

        if ($newBatch) {
            $batches[] = [
                'id' => $newBatch->id,
                'start_hour' => 7,
                'start_minute' => 0,
                'start_period' => 'AM',
                'end_hour' => 8,
                'end_minute' => 0,
                'end_period' => 'AM',
                'buffer_time' => 10,
                'max_size' => $newBatchMaxSize ?? $newBatch->max_size,
            ];
        } else {
            $batches[] = [
                'start_hour' => 7,
                'start_minute' => 0,
                'start_period' => 'AM',
                'end_hour' => 8,
                'end_minute' => 0,
                'end_period' => 'AM',
                'buffer_time' => 10,
                'max_size' => 1,
            ];
        }

        return [
            'name' => $session->name,
            'from_date' => '2026-01-15',
            'to_date' => '2026-06-15',
            'extended_date' => '2026-09-15',
            'status' => Session::STATUS_UPCOMING,
            'form_fee' => 50,
            'ages' => [[
                'id' => $age->id,
                'from_age' => 1,
                'to_age' => 150,
                'fee' => 1000,
                'extended_fee' => 100,
                'extended_fee_late' => 50,
            ]],
            'batches' => $batches,
            'memberships' => [
                [
                    'id' => $normalCard->id,
                    'card_name' => $normalCard->card_name,
                    'no_of_slot' => $normalCard->no_of_slot,
                    'membership_cost' => $normalCard->membership_cost,
                ],
                [
                    'id' => $wildcardCard->id,
                    'card_name' => $wildcardCard->card_name,
                    'no_of_slot' => $wildcardCard->no_of_slot,
                    'membership_cost' => $wildcardCard->membership_cost,
                ],
            ],
        ];
    }
}
