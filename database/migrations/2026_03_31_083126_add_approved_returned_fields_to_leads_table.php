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
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('approved_returned_at')->nullable()->after('returned_to_primary_archived_at');
            $table->foreignId('approved_returned_by_user_id')->nullable()->after('approved_returned_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_returned_by_user_id');
            $table->dropColumn('approved_returned_at');
        });
    }
};
