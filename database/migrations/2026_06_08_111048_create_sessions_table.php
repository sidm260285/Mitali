<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('upcoming');
            $table->unsignedInteger('admission_count')->default(0);
            $table->unsignedInteger('attendance_count')->default(0);
            $table->timestamps();
        });

        Schema::create('session_ages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->unsignedTinyInteger('from_age');
            $table->unsignedTinyInteger('to_age');
            $table->decimal('fee', 10, 2);
            $table->unsignedInteger('admission_counter')->default(0);
            $table->unsignedInteger('attendance_counter')->default(0);
            $table->timestamps();
        });

        Schema::create('session_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('buffer_time');
            $table->unsignedInteger('admission_counter')->default(0);
            $table->unsignedInteger('attendance_counter')->default(0);
            $table->timestamps();
        });

        Schema::create('session_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->string('card_name', 200);
            $table->smallInteger('no_of_slot');
            $table->decimal('membership_cost', 10, 2);
            $table->unsignedInteger('admission_counter')->default(0);
            $table->unsignedInteger('attendance_counter')->default(0);
            $table->timestamps();

            $table->unique(['session_id', 'card_name']);
            $table->unique(['session_id', 'no_of_slot']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_memberships');
        Schema::dropIfExists('session_batches');
        Schema::dropIfExists('session_ages');
        Schema::dropIfExists('training_sessions');
    }
};
