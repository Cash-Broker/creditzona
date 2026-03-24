<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('returned_to_primary_archived_user_id')
                ->nullable()
                ->after('attached_archived_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('returned_to_primary_archived_at')
                ->nullable()
                ->after('returned_to_primary_archived_user_id');

            $table->index(
                ['returned_to_primary_archived_user_id', 'assigned_user_id', 'returned_to_primary_archived_at'],
                'leads_returned_to_me_archive_lookup_index',
            );
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_returned_to_me_archive_lookup_index');
            $table->dropConstrainedForeignId('returned_to_primary_archived_user_id');
            $table->dropColumn('returned_to_primary_archived_at');
        });
    }
};
