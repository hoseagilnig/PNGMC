<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id('enrollment_id');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('program_id')->constrained('programs')->cascadeOnDelete();
            $table->date('enrollment_date');
            $table->string('academic_year', 20)->nullable();
            $table->string('semester', 20)->nullable();
            $table->enum('status', ['enrolled', 'completed', 'dropped', 'transferred'])->default('enrolled');
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('program_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};

