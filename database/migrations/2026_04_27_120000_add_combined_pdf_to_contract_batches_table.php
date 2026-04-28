<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_batches', function (Blueprint $table) {
            $table->string('combined_pdf_path')->nullable()->after('generated_documents');
            $table->string('combined_pdf_file_name')->nullable()->after('combined_pdf_path');
            $table->string('combined_docx_path')->nullable()->after('combined_pdf_file_name');
            $table->string('combined_docx_file_name')->nullable()->after('combined_docx_path');
        });
    }

    public function down(): void
    {
        Schema::table('contract_batches', function (Blueprint $table) {
            $table->dropColumn(['combined_pdf_path', 'combined_pdf_file_name', 'combined_docx_path', 'combined_docx_file_name']);
        });
    }
};
