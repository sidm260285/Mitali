<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('session_ages', function (Blueprint $table) {
            $table->decimal('extended_fee', 10, 2)->default(0)->after('fee');
            $table->decimal('extended_fee_late', 10, 2)->default(0)->after('extended_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('session_ages', function (Blueprint $table) {
            $table->dropColumn(['extended_fee', 'extended_fee_late']);
        });
    }
};
