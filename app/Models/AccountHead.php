<?php

namespace App\Models;

use Database\Factories\AccountHeadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountHead extends Model
{
    /** @use HasFactory<AccountHeadFactory> */
    use HasFactory;

    public const TYPE_CREDIT = 'credit';

    public const TYPE_DEBIT = 'debit';

    public const SYSTEM_ADMIN_TO_EXECUTIVE = 'admin to executive';

    public const SYSTEM_RECEIVE_FROM_ADMIN = 'receive from admin';

    public const SYSTEM_EXECUTIVE_TO_EXECUTIVE = 'executive to executive';

    public const SYSTEM_EXECUTIVE_TO_ADMIN = 'executive to admin';

    public const SYSTEM_EXECUTIVE_TO_BANK = 'executive to bank';

    public const SYSTEM_ADMIN_TO_BANK = 'admin to bank';

    public const SYSTEM_BANK_TO_ADMIN = 'bank to admin';

    public const SYSTEM_BANK_TO_EXECUTIVE = 'bank to executive';

    public const SYSTEM_DEPOSIT_BY_ADMIN = 'deposit by admin';

    public const SYSTEM_DEPOSIT_BY_EXECUTIVE = 'deposit by executive';

    public const SYSTEM_WITHDRAWN_FROM_BANK = 'withdrawn from bank';

    public const SYSTEM_BANK_TO_BANK = 'bank to bank';

    public const SYSTEM_ADMISSION = 'admission';

    public const SYSTEM_REFUND = 'refund';

    public const SYSTEM_SALARY_TO_TRAINER = 'salary to trainer';

    public const SYSTEM_SALARY_TO_EXECUTIVE = 'salary to executive';

    protected $fillable = [
        'name',
        'type',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function isInUse(): bool
    {
        return $this->cashTransactions()->exists();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_CREDIT => 'Credit',
            self::TYPE_DEBIT => 'Debit',
            default => '—',
        };
    }

    public function scopeUserManaged(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeForDropdown(Builder $query, string $type): Builder
    {
        return $query->userManaged()->where('type', $type)->orderBy('name');
    }

    public static function systemHeadNames(): array
    {
        return [
            self::SYSTEM_ADMIN_TO_EXECUTIVE,
            self::SYSTEM_RECEIVE_FROM_ADMIN,
            self::SYSTEM_EXECUTIVE_TO_EXECUTIVE,
            self::SYSTEM_EXECUTIVE_TO_ADMIN,
            self::SYSTEM_EXECUTIVE_TO_BANK,
            self::SYSTEM_ADMIN_TO_BANK,
            self::SYSTEM_BANK_TO_ADMIN,
            self::SYSTEM_BANK_TO_EXECUTIVE,
            self::SYSTEM_DEPOSIT_BY_ADMIN,
            self::SYSTEM_DEPOSIT_BY_EXECUTIVE,
            self::SYSTEM_WITHDRAWN_FROM_BANK,
            self::SYSTEM_BANK_TO_BANK,
            self::SYSTEM_ADMISSION,
            self::SYSTEM_REFUND,
            self::SYSTEM_SALARY_TO_TRAINER,
            self::SYSTEM_SALARY_TO_EXECUTIVE,
        ];
    }

    public static function findSystem(string $name): self
    {
        return self::query()->where('is_system', true)->where('name', $name)->firstOrFail();
    }
}
