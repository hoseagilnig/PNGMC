<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Archived Applications
        Schema::create('archived_applications', function (Blueprint $table) {
            $table->id('archive_id');
            $table->unsignedBigInteger('original_application_id');
            $table->string('application_number', 50);
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
            $table->string('country', 100)->nullable();
            $table->boolean('grade_12_passed')->default(false);
            $table->string('maths_grade', 10)->nullable();
            $table->string('physics_grade', 10)->nullable();
            $table->string('english_grade', 10)->nullable();
            $table->decimal('overall_gpa', 4, 2)->nullable();
            $table->string('program_interest', 200)->nullable();
            $table->date('expression_date')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->enum('status', ['submitted', 'under_review', 'hod_review', 'accepted', 'rejected', 'correspondence_sent', 'checks_pending', 'checks_completed', 'enrolled', 'ineligible'])->default('submitted');
            $table->unsignedBigInteger('assessed_by')->nullable();
            $table->date('assessment_date')->nullable();
            $table->text('assessment_notes')->nullable();
            $table->enum('hod_decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('hod_decision_by')->nullable();
            $table->date('hod_decision_date')->nullable();
            $table->text('hod_decision_notes')->nullable();
            $table->boolean('correspondence_sent')->default(false);
            $table->date('correspondence_date')->nullable();
            $table->boolean('invoice_sent')->default(false);
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->boolean('enrollment_ready')->default(false);
            $table->boolean('enrolled')->default(false);
            $table->date('enrollment_date')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->string('archive_reason', 255)->nullable();
            $table->text('archive_notes')->nullable();
            $table->timestamp('original_created_at')->nullable();
            $table->timestamp('original_updated_at')->nullable();
            
            $table->index('original_application_id');
            $table->index('application_number');
            $table->index('status');
            $table->index('archived_at');
        });

        // Archived Students
        Schema::create('archived_students', function (Blueprint $table) {
            $table->id('archive_id');
            $table->unsignedBigInteger('original_student_id');
            $table->string('student_number', 50);
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
            $table->date('enrollment_date')->nullable();
            $table->date('graduation_date')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'graduated', 'withdrawn', 'suspended'])->default('active');
            $table->enum('account_status', ['active', 'on_hold', 'suspended', 'inactive'])->default('active');
            $table->string('profile_photo_path', 500)->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->string('archive_reason', 255)->nullable();
            $table->text('archive_notes')->nullable();
            $table->timestamp('original_created_at')->nullable();
            $table->timestamp('original_updated_at')->nullable();
            
            $table->index('original_student_id');
            $table->index('student_number');
            $table->index('status');
            $table->index('archived_at');
        });

        // Archived Invoices
        Schema::create('archived_invoices', function (Blueprint $table) {
            $table->id('archive_id');
            $table->unsignedBigInteger('original_invoice_id');
            $table->string('invoice_number', 50);
            $table->unsignedBigInteger('student_id')->nullable();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->enum('invoice_type', ['tuition', 'fees', 'other'])->default('tuition');
            $table->decimal('amount', 10, 2);
            $table->date('due_date')->nullable();
            $table->enum('status', ['pending', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_reference', 100)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('archived_by')->nullable();
            $table->timestamp('archived_at')->useCurrent();
            $table->string('archive_reason', 255)->nullable();
            $table->text('archive_notes')->nullable();
            $table->timestamp('original_created_at')->nullable();
            $table->timestamp('original_updated_at')->nullable();
            
            $table->index('original_invoice_id');
            $table->index('invoice_number');
            $table->index('student_id');
            $table->index('status');
            $table->index('archived_at');
        });

        // Archive Log
        Schema::create('archive_log', function (Blueprint $table) {
            $table->id('log_id');
            $table->enum('archive_type', ['application', 'student', 'invoice', 'document']);
            $table->unsignedBigInteger('original_id');
            $table->enum('action', ['archived', 'restored', 'deleted']);
            $table->unsignedBigInteger('performed_by')->nullable();
            $table->timestamp('performed_at')->useCurrent();
            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();
            
            $table->index('archive_type');
            $table->index('original_id');
            $table->index('action');
            $table->index('performed_at');
        });

        // Archive Settings
        Schema::create('archive_settings', function (Blueprint $table) {
            $table->id('setting_id');
            $table->string('setting_key', 100)->unique();
            $table->string('setting_value', 255)->nullable();
            $table->text('setting_description')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            
            $table->index('setting_key');
        });

        // Insert default archive settings
        DB::table('archive_settings')->insert([
            ['setting_key' => 'auto_archive_applications', 'setting_value' => 'false', 'setting_description' => 'Automatically archive applications older than specified days'],
            ['setting_key' => 'archive_applications_after_days', 'setting_value' => '365', 'setting_description' => 'Archive applications completed/rejected more than this many days ago'],
            ['setting_key' => 'auto_archive_students', 'setting_value' => 'false', 'setting_description' => 'Automatically archive inactive/graduated students'],
            ['setting_key' => 'archive_students_after_days', 'setting_value' => '730', 'setting_description' => 'Archive students inactive/graduated more than this many days ago'],
            ['setting_key' => 'auto_archive_invoices', 'setting_value' => 'false', 'setting_description' => 'Automatically archive paid invoices'],
            ['setting_key' => 'archive_invoices_after_days', 'setting_value' => '180', 'setting_description' => 'Archive paid invoices older than this many days'],
            ['setting_key' => 'archive_keep_documents', 'setting_value' => 'true', 'setting_description' => 'Keep document files when archiving (do not delete)'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('archive_settings');
        Schema::dropIfExists('archive_log');
        Schema::dropIfExists('archived_invoices');
        Schema::dropIfExists('archived_students');
        Schema::dropIfExists('archived_applications');
    }
};

