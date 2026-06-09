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

    protected $fillable = [
        'user_id',
        'account_head_id',
        'type',
        'amount',
        'transaction_date',
        'narration',
        'current_balance',
        'transfer_group_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'transaction_date' => 'date',
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
