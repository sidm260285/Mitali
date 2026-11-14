<?php

namespace Tests\Feature;

use App\Models\CashTransaction;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\SystemAccountHeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalaryPaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $executive;
    private User $bank;
    private Trainer $trainer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemAccountHeadSeeder::class);
        $this->admin = User::factory()->admin()->create(['balance' => 100000]);
        $this->executive = User::factory()->executive()->create(['monthly_salary' => 20000]);
        $this->bank = User::factory()->bank()->create(['balance' => 50000]);
        $this->trainer = Trainer::factory()->create(['monthly_salary' => 30000]);
    }

    // ── Page access ──────────────────────────────────────────────────────────

    public function test_admin_can_view_executive_salary_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.salary-payment.executives.index'))
            ->assertOk()
            ->assertSee('Pay Salary to Executives');
    }

    public function test_admin_can_view_trainer_salary_page(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.salary-payment.trainers.index'))
            ->assertOk()
            ->assertSee('Pay Salary to Trainers');
    }

    public function test_executive_cannot_access_salary_pages(): void
    {
        $this->actingAs($this->executive)
            ->get(route('admin.salary-payment.executives.index'))
            ->assertForbidden();
    }

    // ── Executive list with month/year ───────────────────────────────────────

    public function test_executive_list_shows_salary_status(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->get(route('admin.salary-payment.executives.index', ['month' => $month, 'year' => $year]))
            ->assertOk()
            ->assertSee($this->executive->name)
            ->assertSee('Pay');
    }

    public function test_trainer_list_shows_salary_status(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->get(route('admin.salary-payment.trainers.index', ['month' => $month, 'year' => $year]))
            ->assertOk()
            ->assertSee($this->trainer->name)
            ->assertSee('Pay');
    }

    // ── Cash mode salary payment ─────────────────────────────────────────────

    public function test_admin_can_pay_executive_salary_via_cash(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 20000,
                'mode' => 'cash',
            ])
            ->assertRedirect(route('admin.salary-payment.executives.index', ['month' => $month, 'year' => $year]))
            ->assertSessionHas('success');

        $this->assertEquals(80000, (float) $this->admin->fresh()->balance);

        $txn = CashTransaction::where('salary_type', 'executive')->first();
        $this->assertNotNull($txn);
        $this->assertEquals($this->executive->id, $txn->to_salary_id);
        $this->assertEquals($month, $txn->salary_month);
        $this->assertEquals($year, $txn->salary_year);
        $this->assertEquals('debit', $txn->type);
        $this->assertEquals('cash', $txn->mode);
        $this->assertEquals($this->admin->id, $txn->user_id);
    }

    public function test_admin_can_pay_trainer_salary_via_cash(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'trainer',
                'payee_id' => $this->trainer->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 30000,
                'mode' => 'cash',
            ])
            ->assertRedirect(route('admin.salary-payment.trainers.index', ['month' => $month, 'year' => $year]))
            ->assertSessionHas('success');

        $this->assertEquals(70000, (float) $this->admin->fresh()->balance);

        $txn = CashTransaction::where('salary_type', 'trainer')->first();
        $this->assertNotNull($txn);
        $this->assertEquals($this->trainer->id, $txn->to_salary_id);
        $this->assertEquals('salary to trainer', $txn->accountHead->name);
    }

    // ── Bank mode salary payment ─────────────────────────────────────────────

    public function test_admin_can_pay_salary_via_bank(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 20000,
                'mode' => 'bank',
                'bank_id' => $this->bank->id,
                'transaction_id' => 'TXN-SAL-001',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(100000, (float) $this->admin->fresh()->balance);
        $this->assertEquals(30000, (float) $this->bank->fresh()->balance);

        $txn = CashTransaction::where('salary_type', 'executive')->first();
        $this->assertEquals($this->bank->id, $txn->user_id);
        $this->assertEquals('bank', $txn->mode);
        $this->assertEquals('TXN-SAL-001', $txn->transaction_id);
        $this->assertEquals($this->admin->id, $txn->entry_by);
    }

    // ── Part payment ─────────────────────────────────────────────────────────

    public function test_admin_can_make_part_payment(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 8000,
                'mode' => 'cash',
            ])
            ->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 12000,
                'mode' => 'cash',
            ])
            ->assertSessionHas('success');

        $totalPaid = CashTransaction::salaryPaidAmount('executive', $this->executive->id, $month, $year);
        $this->assertEquals(20000, $totalPaid);
        $this->assertEquals(80000, (float) $this->admin->fresh()->balance);
    }

    // ── Overpayment blocked ──────────────────────────────────────────────────

    public function test_cannot_pay_more_than_salary(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 20001,
                'mode' => 'cash',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_cannot_exceed_salary_with_part_payments(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 15000,
                'mode' => 'cash',
            ]);

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 5001,
                'mode' => 'cash',
            ])
            ->assertSessionHasErrors('amount');
    }

    // ── Insufficient balance ─────────────────────────────────────────────────

    public function test_insufficient_cash_balance_is_rejected(): void
    {
        $admin = User::factory()->admin()->create(['balance' => 100]);

        $this->actingAs($admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 20000,
                'mode' => 'cash',
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_insufficient_bank_balance_is_rejected(): void
    {
        $poorBank = User::factory()->bank()->create(['balance' => 100]);

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 20000,
                'mode' => 'bank',
                'bank_id' => $poorBank->id,
                'transaction_id' => 'TXN-FAIL',
            ])
            ->assertSessionHasErrors('amount');
    }

    // ── Month/year validation ────────────────────────────────────────────────

    public function test_past_month_salary_is_allowed(): void
    {
        $pastDate = now()->subMonths(3);

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) $pastDate->month,
                'salary_year' => (int) $pastDate->year,
                'amount' => 10000,
                'mode' => 'cash',
            ])
            ->assertSessionHas('success');
    }

    public function test_one_future_month_is_allowed(): void
    {
        $nextMonth = now()->addMonth();

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) $nextMonth->month,
                'salary_year' => (int) $nextMonth->year,
                'amount' => 10000,
                'mode' => 'cash',
            ])
            ->assertSessionHas('success');
    }

    public function test_two_months_future_is_rejected(): void
    {
        $farFuture = now()->addMonths(2);

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) $farFuture->month,
                'salary_year' => (int) $farFuture->year,
                'amount' => 10000,
                'mode' => 'cash',
            ])
            ->assertSessionHasErrors('salary_month');
    }

    // ── Bank mode requires fields ────────────────────────────────────────────

    public function test_bank_mode_requires_bank_id_and_transaction_id(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 10000,
                'mode' => 'bank',
            ])
            ->assertSessionHasErrors(['bank_id', 'transaction_id']);
    }

    // ── Narration is optional ────────────────────────────────────────────────

    public function test_narration_is_optional(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 5000,
                'mode' => 'cash',
                'narration' => 'Part payment for July',
            ])
            ->assertSessionHas('success');

        $txn = CashTransaction::where('salary_type', 'executive')->first();
        $this->assertEquals('Part payment for July', $txn->narration);
    }

    // ── Salary shows in transaction lists ────────────────────────────────────

    public function test_cash_salary_appears_in_admin_transaction_list(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 5000,
                'mode' => 'cash',
            ]);

        $this->actingAs($this->admin)
            ->get(route('admin.cash-flow.transactions.index'))
            ->assertOk()
            ->assertSee('salary to executive');
    }

    public function test_bank_salary_appears_in_bank_transaction_list(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'trainer',
                'payee_id' => $this->trainer->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 10000,
                'mode' => 'bank',
                'bank_id' => $this->bank->id,
                'transaction_id' => 'SAL-BANK-001',
            ]);

        $this->actingAs($this->admin)
            ->get(route('admin.bank-flow.transactions.index', ['bank_id' => $this->bank->id]))
            ->assertOk()
            ->assertSee('salary to trainer');
    }

    // ── Fully paid shows badge, no pay button ────────────────────────────────

    public function test_fully_paid_shows_paid_badge(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 20000,
                'mode' => 'cash',
            ]);

        $this->actingAs($this->admin)
            ->get(route('admin.salary-payment.executives.index', ['month' => $month, 'year' => $year]))
            ->assertOk()
            ->assertSee('Paid')
            ->assertDontSee('data-payee-id="'.$this->executive->id.'"');
    }

    // ── Inactive payee is rejected ───────────────────────────────────────────

    public function test_inactive_executive_salary_is_rejected(): void
    {
        $inactiveExec = User::factory()->executive()->inactive()->create(['monthly_salary' => 15000]);

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $inactiveExec->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 10000,
                'mode' => 'cash',
            ])
            ->assertNotFound();
    }

    public function test_duplicate_transaction_id_for_same_bank_is_rejected(): void
    {
        $month = (int) now()->month;
        $year = (int) now()->year;

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 5000,
                'mode' => 'bank',
                'bank_id' => $this->bank->id,
                'transaction_id' => 'DUP-TXN-001',
            ])
            ->assertSessionHas('success');

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'executive',
                'payee_id' => $this->executive->id,
                'salary_month' => $month,
                'salary_year' => $year,
                'amount' => 5000,
                'mode' => 'bank',
                'bank_id' => $this->bank->id,
                'transaction_id' => 'DUP-TXN-001',
            ])
            ->assertSessionHasErrors('transaction_id');
    }

    public function test_inactive_trainer_salary_is_rejected(): void
    {
        $inactiveTrainer = Trainer::factory()->inactive()->create(['monthly_salary' => 20000]);

        $this->actingAs($this->admin)
            ->post(route('admin.salary-payment.store'), [
                'payee_type' => 'trainer',
                'payee_id' => $inactiveTrainer->id,
                'salary_month' => (int) now()->month,
                'salary_year' => (int) now()->year,
                'amount' => 10000,
                'mode' => 'cash',
            ])
            ->assertNotFound();
    }
}
