<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id('student_id');
            $table->string('student_number', 20)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('email', 100)->nullable();
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('province', 100)->nullable();
            $table->string('country', 100)->default('Papua New Guinea');
            $table->string('emergency_contact_name', 100)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'suspended', 'withdrawn'])->default('active');
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->timestamps();
            
            $table->index('student_number');
            $table->index('status');
            $table->index('program_id');
            $table->index(['last_name', 'first_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

