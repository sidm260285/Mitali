<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_EXECUTIVE = 'executive';

    public const ROLE_BANK = 'bank';

    public const ACCOUNT_TYPE_SAVINGS = 'Savings';

    public const ACCOUNT_TYPE_CURRENT = 'Current';

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'address',
        'password',
        'role',
        'is_active',
        'must_change_password',
        'balance',
        'account_no',
        'account_type',
        'branch_name',
        'monthly_salary',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'balance' => 'decimal:2',
            'monthly_salary' => 'integer',
        ];
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function freshBalance(): float
    {
        return (float) self::where('id', $this->id)->value('balance');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isExecutive(): bool
    {
        return $this->role === self::ROLE_EXECUTIVE;
    }

    public function isBank(): bool
    {
        return $this->role === self::ROLE_BANK;
    }

    public function dashboardRoute(): string
    {
        if ($this->isAdmin()) {
            return route('admin.dashboard');
        }

        if ($this->isBank()) {
            return route('bank.dashboard');
        }

        return route('executive.dashboard');
    }

    public static function accountTypeOptions(): array
    {
        return [
            self::ACCOUNT_TYPE_SAVINGS => self::ACCOUNT_TYPE_SAVINGS,
            self::ACCOUNT_TYPE_CURRENT => self::ACCOUNT_TYPE_CURRENT,
        ];
    }

    public function scopeExecutives(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_EXECUTIVE);
    }

    public function scopeBanks(Builder $query): Builder
    {
        return $query->where('role', self::ROLE_BANK);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

}
