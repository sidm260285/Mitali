<?php

namespace App\Http\Controllers\SalaryPayment;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalaryPaymentRequest;
use App\Models\CashTransaction;
use App\Models\Trainer;
use App\Models\User;
use App\Services\CashTransactionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalaryPaymentController extends Controller
{
    public function __construct(
        private CashTransactionService $cashTransactions,
    ) {}

    public function executiveIndex(Request $request): View
    {
        [$month, $year] = $this->resolveMonthYear($request);

        $executives = null;

        if ($month && $year) {
            $executives = User::executives()
                ->active()
                ->orderBy('name')
                ->get()
                ->map(function (User $executive) use ($month, $year) {
                    $paid = CashTransaction::salaryPaidAmount(
                        CashTransaction::SALARY_TYPE_EXECUTIVE,
                        $executive->id,
                        $month,
                        $year,
                    );

                    return (object) [
                        'id' => $executive->id,
                        'name' => $executive->name,
                        'phone' => $executive->phone,
                        'monthly_salary' => $executive->monthly_salary,
                        'paid_amount' => $paid,
                        'remaining' => $executive->monthly_salary - $paid,
                        'fully_paid' => $paid >= $executive->monthly_salary,
                    ];
                });
        }

        return view('salary-payment.executive-index', [
            'payees' => $executives,
            'payeeType' => CashTransaction::SALARY_TYPE_EXECUTIVE,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'banks' => User::banks()->active()->orderBy('name')->get(),
            'routeName' => 'admin.salary-payment.executives.index',
            'title' => 'Pay Salary to Executives',
        ]);
    }

    public function trainerIndex(Request $request): View
    {
        [$month, $year] = $this->resolveMonthYear($request);

        $trainers = null;

        if ($month && $year) {
            $trainers = Trainer::where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(function (Trainer $trainer) use ($month, $year) {
                    $paid = CashTransaction::salaryPaidAmount(
                        CashTransaction::SALARY_TYPE_TRAINER,
                        $trainer->id,
                        $month,
                        $year,
                    );

                    return (object) [
                        'id' => $trainer->id,
                        'name' => $trainer->name,
                        'phone' => $trainer->mobile,
                        'monthly_salary' => $trainer->monthly_salary,
                        'paid_amount' => $paid,
                        'remaining' => $trainer->monthly_salary - $paid,
                        'fully_paid' => $paid >= $trainer->monthly_salary,
                    ];
                });
        }

        return view('salary-payment.trainer-index', [
            'payees' => $trainers,
            'payeeType' => CashTransaction::SALARY_TYPE_TRAINER,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'monthOptions' => $this->monthOptions(),
            'yearOptions' => $this->yearOptions(),
            'banks' => User::banks()->active()->orderBy('name')->get(),
            'routeName' => 'admin.salary-payment.trainers.index',
            'title' => 'Pay Salary to Trainers',
        ]);
    }

    public function store(StoreSalaryPaymentRequest $request): RedirectResponse
    {
        $bank = null;

        if ($request->input('mode') === CashTransaction::MODE_BANK) {
            $bank = User::banks()->active()->findOrFail($request->integer('bank_id'));
        }

        $this->cashTransactions->paySalary(
            doer: $request->user(),
            payeeType: $request->input('payee_type'),
            payeeId: $request->integer('payee_id'),
            salaryMonth: $request->integer('salary_month'),
            salaryYear: $request->integer('salary_year'),
            amount: (float) $request->input('amount'),
            mode: $request->input('mode'),
            bank: $bank,
            transactionId: $request->input('transaction_id'),
            narration: $request->input('narration'),
        );

        $redirectRoute = $request->input('payee_type') === CashTransaction::SALARY_TYPE_EXECUTIVE
            ? 'admin.salary-payment.executives.index'
            : 'admin.salary-payment.trainers.index';

        return redirect()
            ->route($redirectRoute, [
                'month' => $request->integer('salary_month'),
                'year' => $request->integer('salary_year'),
            ])
            ->with('success', 'Salary payment recorded successfully.');
    }

    private function resolveMonthYear(Request $request): array
    {
        $month = $request->integer('month') ?: null;
        $year = $request->integer('year') ?: null;

        if ($month && ($month < 1 || $month > 12)) {
            $month = null;
        }

        if ($year && ($year < 2020 || $year > 2099)) {
            $year = null;
        }

        if ($month && $year) {
            $now = now();
            $maxPeriod = ((int) $now->year) * 12 + (int) $now->month + 1;
            $selectedPeriod = $year * 12 + $month;

            if ($selectedPeriod > $maxPeriod) {
                $month = null;
                $year = null;
            }
        }

        return [$month, $year];
    }

    private function monthOptions(): array
    {
        return [
            1 => 'January', 2 => 'February', 3 => 'March',
            4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September',
            10 => 'October', 11 => 'November', 12 => 'December',
        ];
    }

    private function yearOptions(): array
    {
        $currentYear = (int) now()->year;
        $years = [];

        for ($y = $currentYear + 1; $y >= 2020; $y--) {
            $years[$y] = (string) $y;
        }

        return $years;
    }
}
