<?php

use App\Models\AccountHead;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            AccountHead::SYSTEM_ADMISSION,
            AccountHead::SYSTEM_REFUND,
            AccountHead::SYSTEM_SALARY_TO_TRAINER,
            AccountHead::SYSTEM_SALARY_TO_EXECUTIVE,
        ] as $name) {
            AccountHead::query()->updateOrCreate(
                ['name' => $name, 'type' => null],
                ['is_system' => true],
            );
        }
    }

    public function down(): void
    {
        AccountHead::query()
            ->whereIn('name', [
                AccountHead::SYSTEM_ADMISSION,
                AccountHead::SYSTEM_REFUND,
                AccountHead::SYSTEM_SALARY_TO_TRAINER,
                AccountHead::SYSTEM_SALARY_TO_EXECUTIVE,
            ])
            ->where('is_system', true)
            ->delete();
    }
};
