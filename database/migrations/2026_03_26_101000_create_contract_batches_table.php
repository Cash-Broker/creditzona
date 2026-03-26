<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_batches', function (Blueprint $table): void {
            $table->id();
            $table->string('company_key', 64);
            $table->string('client_full_name');
            $table->string('co_applicant_full_name')->nullable();
            $table->date('request_date');
            $table->json('selected_document_types');
            $table->longText('input_payload');
            $table->json('generated_documents')->nullable();
            $table->string('archive_path')->nullable();
            $table->string('archive_file_name')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('company_key');
            $table->index('request_date');
            $table->index('generated_at');
            $table->index('created_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_batches');
    }
};
