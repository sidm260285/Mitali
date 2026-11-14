<?php

use App\Models\AccountHead;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        AccountHead::query()->updateOrCreate(
            ['name' => AccountHead::SYSTEM_WITHDRAWN_FROM_BANK, 'type' => null],
            ['is_system' => true],
        );
    }

    public function down(): void
    {
        AccountHead::query()
            ->where('name', AccountHead::SYSTEM_WITHDRAWN_FROM_BANK)
            ->where('is_system', true)
            ->delete();
    }
};
