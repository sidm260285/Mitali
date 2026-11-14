<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->enum('salary_type', ['executive', 'trainer'])->nullable()->default(null)->after('entry_by');
            $table->unsignedBigInteger('to_salary_id')->default(0)->after('salary_type');
            $table->unsignedTinyInteger('salary_month')->nullable()->after('to_salary_id');
            $table->unsignedSmallInteger('salary_year')->nullable()->after('salary_month');

            $table->index(['salary_type', 'to_salary_id', 'salary_month', 'salary_year'], 'cash_txn_salary_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex('cash_txn_salary_lookup');
            $table->dropColumn(['salary_type', 'to_salary_id', 'salary_month', 'salary_year']);
        });
    }
};
