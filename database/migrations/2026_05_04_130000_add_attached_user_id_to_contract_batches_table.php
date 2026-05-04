<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_batches', function (Blueprint $table): void {
            $table->foreignId('attached_user_id')
                ->nullable()
                ->after('created_by_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->index('attached_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('contract_batches', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('attached_user_id');
        });
    }
};
