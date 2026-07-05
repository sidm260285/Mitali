<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('training_sessions')->cascadeOnDelete();
            $table->foreignId('session_membership_id')->constrained('session_memberships');
            $table->foreignId('session_age_id')->constrained('session_ages');
            $table->foreignId('cash_transaction_id')->nullable()->constrained('cash_transactions')->nullOnDelete();

            $table->string('full_name');
            $table->string('gender');
            $table->date('date_of_birth');
            $table->string('mobile_no', 15);
            $table->string('guardian_name');
            $table->string('relation');
            $table->string('emergency_contact_no', 15);
            $table->text('address');
            $table->string('police_station');
            $table->string('pin_code', 10);

            $table->string('rfid_code');
            $table->boolean('is_wildcard')->default(false);
            $table->decimal('amount', 10, 2);
            $table->string('payment_mode', 10);
            $table->boolean('is_document_complete')->default(false);

            $table->unsignedBigInteger('admitted_by');
            $table->timestamps();

            $table->unique(['session_id', 'rfid_code']);
            $table->index(['session_id', 'is_document_complete']);
        });

        Schema::create('admission_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->foreignId('session_batch_id')->constrained('session_batches')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['admission_id', 'session_batch_id']);
        });

        Schema::create('admission_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admission_id')->constrained('admissions')->cascadeOnDelete();
            $table->string('type', 30);
            $table->string('file_path');
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedInteger('file_size');
            $table->timestamps();

            $table->index(['admission_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admission_documents');
        Schema::dropIfExists('admission_slots');
        Schema::dropIfExists('admissions');
    }
};
