<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('archived_additional_user_id')
                ->nullable()
                ->after('returned_to_primary_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('attached_archived_at')
                ->nullable()
                ->after('archived_additional_user_id');

            $table->index(
                ['archived_additional_user_id', 'additional_user_id', 'attached_archived_at'],
                'leads_attached_archive_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_attached_archive_lookup_index');
            $table->dropConstrainedForeignId('archived_additional_user_id');
            $table->dropColumn('attached_archived_at');
        });
    }
};
