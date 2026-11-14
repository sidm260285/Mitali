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
        Schema::table('admissions', function (Blueprint $table) {
            $table->date('session_start_date')->after('session_id');
            $table->date('session_end_date')->after('session_start_date');
            $table->boolean('is_extended_membership')->default(false)->after('session_end_date');
        });

        Schema::create('fee_breakdowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('admission_id')->unique()->constrained('admissions')->cascadeOnDelete();
            $table->decimal('membership_amount', 10, 2);
            $table->decimal('age_fee', 10, 2);
            $table->decimal('form_fee', 10, 2);
            $table->decimal('extended_fee', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_breakdowns');

        Schema::table('admissions', function (Blueprint $table) {
            $table->dropColumn([
                'session_start_date',
                'session_end_date',
                'is_extended_membership',
            ]);
        });
    }
};
