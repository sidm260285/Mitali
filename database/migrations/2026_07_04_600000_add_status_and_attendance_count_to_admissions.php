<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('is_document_complete');
            $table->unsignedInteger('attendance_count')->default(0)->after('status');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::table('admissions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'attendance_count']);
        });
    }
};
