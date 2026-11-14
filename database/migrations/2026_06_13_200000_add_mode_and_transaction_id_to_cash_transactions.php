<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->enum('mode', ['cash', 'bank'])->default('cash')->after('transfer_group_id');
            $table->string('transaction_id')->nullable()->after('mode');
        });

        DB::statement('ALTER TABLE cash_transactions ALTER mode DROP DEFAULT');

        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->unique(['user_id', 'transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'transaction_id']);
            $table->dropColumn(['mode', 'transaction_id']);
        });
    }
};
