<?php

namespace App\Models;

use Database\Factories\CashTransactionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    /** @use HasFactory<CashTransactionFactory> */
    use HasFactory;

    public const TYPE_CREDIT = 'credit';

    public const TYPE_DEBIT = 'debit';

    public const MODE_CASH = 'cash';

    public const MODE_BANK = 'bank';

    public const SALARY_TYPE_EXECUTIVE = 'executive';

    public const SALARY_TYPE_TRAINER = 'trainer';

    protected $fillable = [
        'user_id',
        'account_head_id',
        'type',
        'amount',
        'transaction_date',
        'narration',
        'current_balance',
        'transfer_group_id',
        'mode',
        'transaction_id',
        'entry_by',
        'salary_type',
        'to_salary_id',
        'salary_month',
        'salary_year',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'transaction_date' => 'date',
            'to_salary_id' => 'integer',
            'salary_month' => 'integer',
            'salary_year' => 'integer',
        ];
    }

    public static function salaryPaidAmount(string $salaryType, int $payeeId, int $month, int $year): float
    {
        return (float) self::query()
            ->where('salary_type', $salaryType)
            ->where('to_salary_id', $payeeId)
            ->where('salary_month', $month)
            ->where('salary_year', $year)
            ->sum('amount');
    }

    public function isSalaryPayment(): bool
    {
        return $this->salary_type !== null;
    }

    public function salaryDetail(): ?array
    {
        if (! $this->isSalaryPayment()) {
            return null;
        }

        $months = [1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
            7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'];

        if ($this->salary_type === self::SALARY_TYPE_EXECUTIVE) {
            $payee = User::find($this->to_salary_id);
            $payeeName = $payee?->name ?? '—';
            $monthlySalary = $payee?->monthly_salary ?? 0;
        } else {
            $payee = Trainer::find($this->to_salary_id);
            $payeeName = $payee?->name ?? '—';
            $monthlySalary = $payee?->monthly_salary ?? 0;
        }

        $totalPaid = self::salaryPaidAmount($this->salary_type, $this->to_salary_id, $this->salary_month, $this->salary_year);

        return [
            'salary_for' => ucfirst($this->salary_type),
            'paid_to' => $payeeName,
            'salary_period' => ($months[$this->salary_month] ?? '—').' '.$this->salary_year,
            'monthly_salary' => \App\Support\MoneyHelper::format($monthlySalary),
            'total_paid' => \App\Support\MoneyHelper::format($totalPaid),
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function accountHead(): BelongsTo
    {
        return $this->belongsTo(AccountHead::class);
    }

    public function typeLabel(): string
    {
        return ucfirst($this->type);
    }

    public function narrationPreview(int $length = 50): string
    {
        if (! $this->narration) {
            return '—';
        }

        if (mb_strlen($this->narration) <= $length) {
            return $this->narration;
        }

        return mb_substr($this->narration, 0, $length).'…';
    }

    public function linkedTransactions()
    {
        if (! $this->transfer_group_id) {
            $this->load(['accountHead', 'user']);

            return collect([$this]);
        }

        return self::query()
            ->with(['accountHead', 'user'])
            ->where('transfer_group_id', $this->transfer_group_id)
            ->orderBy('id')
            ->get();
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeFilterDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        if ($from) {
            $query->whereDate('transaction_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('transaction_date', '<=', $to);
        }

        return $query;
    }
}
