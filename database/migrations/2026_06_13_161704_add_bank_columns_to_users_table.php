<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('account_no', 200)->nullable()->after('balance');
            $table->string('account_type', 200)->nullable()->after('account_no');
            $table->string('branch_name', 200)->nullable()->after('account_type');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['account_no', 'account_type', 'branch_name']);
        });
    }
};
