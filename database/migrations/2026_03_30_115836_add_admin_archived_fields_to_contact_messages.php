<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->timestamp('admin_archived_at')->nullable()->after('archived_at');
            $table->foreignId('admin_archived_by_user_id')->nullable()->after('archived_by_user_id')
                ->constrained('users')->nullOnDelete();
        });

        DB::table('contact_messages')
            ->whereNotNull('archived_at')
            ->whereIn('archived_by_user_id', function ($query) {
                $query->select('id')->from('users')->where('role', 'admin');
            })
            ->update([
                'admin_archived_at' => DB::raw('archived_at'),
                'admin_archived_by_user_id' => DB::raw('archived_by_user_id'),
                'archived_at' => null,
                'archived_by_user_id' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('admin_archived_by_user_id');
            $table->dropColumn('admin_archived_at');
        });
    }
};
