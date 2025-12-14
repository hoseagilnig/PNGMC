<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('continuing_student_requirements', function (Blueprint $table) {
            $table->id('requirement_id');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->enum('requirement_type', ['nmsa_approval', 'sea_service_record', 'expression_of_interest', 'coc_validity', 'academic_prerequisites', 'financial_clearance', 'other']);
            $table->string('requirement_name', 200);
            $table->enum('status', ['pending', 'met', 'not_met', 'shortfall_identified'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('verified_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('application_id');
            $table->index('status');
            $table->index('requirement_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('continuing_student_requirements');
    }
};

