<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_batches', function (Blueprint $table): void {
            $table->json('generated_document_history')->nullable()->after('generated_documents');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_batches', function (Blueprint $table): void {
            $table->dropColumn('generated_document_history');
        });
    }
};
