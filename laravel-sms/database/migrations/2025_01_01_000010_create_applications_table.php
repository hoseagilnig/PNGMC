<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id('application_id');
            $table->string('application_number', 50)->unique();
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
            
            // Academic Information
            $table->boolean('grade_12_passed')->default(false);
            $table->string('maths_grade', 10)->nullable();
            $table->string('physics_grade', 10)->nullable();
            $table->string('english_grade', 10)->nullable();
            $table->decimal('overall_gpa', 4, 2)->nullable();
            
            // Application Details
            $table->string('program_interest', 200)->default('Cadet Officers Program');
            $table->enum('application_type', ['new_student', 'continuing_student_solas', 'continuing_student_next_level'])->default('new_student');
            $table->enum('course_type', ['Nautical', 'Engineering'])->nullable();
            $table->date('expression_date');
            $table->timestamp('submitted_at')->useCurrent();
            
            // Workflow Status
            $table->enum('status', ['submitted', 'under_review', 'hod_review', 'accepted', 'rejected', 'correspondence_sent', 'checks_pending', 'checks_completed', 'enrolled', 'ineligible'])->default('submitted');
            
            // Assessment
            $table->foreignId('assessed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('assessment_date')->nullable();
            $table->text('assessment_notes')->nullable();
            
            // HOD Decision
            $table->enum('hod_decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('hod_decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('hod_decision_date')->nullable();
            $table->text('hod_decision_notes')->nullable();
            
            // Correspondence
            $table->boolean('correspondence_sent')->default(false);
            $table->date('correspondence_date')->nullable();
            $table->boolean('invoice_sent')->default(false);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            
            // Enrollment
            $table->boolean('enrollment_ready')->default(false);
            $table->boolean('enrolled')->default(false);
            $table->date('enrollment_date')->nullable();
            $table->foreignId('student_id')->nullable()->constrained('students')->nullOnDelete();
            
            // Continuing Student Fields
            $table->string('nmsa_approval_letter_path', 500)->nullable();
            $table->string('sea_service_record_path', 500)->nullable();
            $table->string('coc_number', 100)->nullable();
            $table->date('coc_expiry_date')->nullable();
            $table->foreignId('previous_student_id')->nullable()->constrained('students')->nullOnDelete();
            $table->boolean('requirements_met')->default(false);
            $table->text('requirements_notes')->nullable();
            $table->text('shortfalls_identified')->nullable();
            $table->boolean('shortfalls_addressed')->default(false);
            
            $table->timestamps();
            
            $table->index('application_number');
            $table->index('status');
            $table->index('hod_decision');
            $table->index('submitted_at');
            $table->index('application_type');
            $table->index('course_type');
            $table->index('requirements_met');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};

