<?php

namespace Database\Seeders;

use App\Models\AccountHead;
use Illuminate\Database\Seeder;

class SystemAccountHeadSeeder extends Seeder
{
    public function run(): void
    {
        foreach (AccountHead::systemHeadNames() as $name) {
            AccountHead::query()->updateOrCreate(
                ['name' => $name, 'type' => null],
                ['is_system' => true],
            );
        }
    }
}
