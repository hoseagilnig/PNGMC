<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('advising_appointments', function (Blueprint $table) {
            $table->id('appointment_id');
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('advisor_id')->constrained('users')->cascadeOnDelete();
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->integer('duration_minutes')->default(30);
            $table->string('subject', 200)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->timestamps();
            
            $table->index('student_id');
            $table->index('advisor_id');
            $table->index('appointment_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('advising_appointments');
    }
};

