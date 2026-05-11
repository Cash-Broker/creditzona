<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('approved_returned_archived_user_id')
                ->nullable()
                ->after('approved_returned_by_user_id')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_returned_archived_at')
                ->nullable()
                ->after('approved_returned_archived_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('approved_returned_archived_user_id');
            $table->dropColumn('approved_returned_archived_at');
        });
    }
};
