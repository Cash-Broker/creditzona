<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->timestamp('additional_assigned_at')
                ->nullable()
                ->after('additional_user_id');

            $table->index(
                ['additional_user_id', 'attached_archived_at', 'additional_assigned_at'],
                'leads_attached_to_user_sort_index',
            );
        });

        // Backfill currently attached leads so the "Закачени към мен" listing has a
        // deterministic order instead of NULLs. The exact attachment moment was never
        // recorded, so use updated_at as the closest available proxy.
        DB::table('leads')
            ->whereNotNull('additional_user_id')
            ->update(['additional_assigned_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropIndex('leads_attached_to_user_sort_index');
            $table->dropColumn('additional_assigned_at');
        });
    }
};
