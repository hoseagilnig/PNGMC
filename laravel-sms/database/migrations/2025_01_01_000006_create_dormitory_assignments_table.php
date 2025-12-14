<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dormitory_assignments', function (Blueprint $table) {
            $table->id('assignment_id');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('dormitory_id')->constrained('dormitories')->cascadeOnDelete();
            $table->date('assignment_date');
            $table->date('check_in_date')->nullable();
            $table->date('check_out_date')->nullable();
            $table->enum('status', ['assigned', 'checked_in', 'checked_out', 'cancelled'])->default('assigned');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('dormitory_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dormitory_assignments');
    }
};

