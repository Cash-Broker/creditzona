<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_batches', function (Blueprint $table): void {
            $table->string('document_layout')->nullable()->after('company_key');
            $table->string('client_city')->nullable()->after('client_full_name');
        });
    }

    public function down(): void
    {
        Schema::table('contract_batches', function (Blueprint $table): void {
            $table->dropColumn(['document_layout', 'client_city']);
        });
    }
};
