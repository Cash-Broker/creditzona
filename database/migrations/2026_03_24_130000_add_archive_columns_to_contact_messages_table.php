<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->foreignId('archived_by_user_id')
                ->nullable()
                ->after('assigned_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('archived_at')
                ->nullable()
                ->after('archived_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archived_by_user_id');
            $table->dropColumn('archived_at');
        });
    }
};
