<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mandatory_checks', function (Blueprint $table) {
            $table->id('check_id');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->enum('check_type', ['medical', 'police_clearance', 'academic_verification', 'identity_verification', 'financial_clearance', 'other']);
            $table->string('check_name', 200);
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->date('completed_date')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('status');
            $table->index('check_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mandatory_checks');
    }
};

