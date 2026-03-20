<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('returned_additional_user_id')
                ->nullable()
                ->after('additional_user_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('returned_to_primary_at')
                ->nullable()
                ->after('returned_additional_user_id');
            $table->index(
                ['returned_additional_user_id', 'additional_user_id'],
                'leads_returned_archive_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_returned_archive_lookup_index');
            $table->dropConstrainedForeignId('returned_additional_user_id');
            $table->dropColumn('returned_to_primary_at');
        });
    }
};
