<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id('document_id');
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->enum('document_type', [
                'grade_12_certificate',
                'transcript',
                'birth_certificate',
                'medical_certificate',
                'police_clearance',
                'passport_photo',
                'nmsa_approval_letter',
                'sea_service_record',
                'coc_certificate',
                'previous_certificates',
                'other'
            ]);
            $table->string('document_name', 200);
            $table->string('file_path', 500)->nullable();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->boolean('verified')->default(false);
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            
            $table->index('application_id');
            $table->index('document_type');
            $table->index('verified');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};

