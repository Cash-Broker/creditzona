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
            // Source/device of a public submission. Indexed so spam can be
            // grouped by IP. Captured server-side only (never client-supplied).
            $table->string('ip_address', 45)->nullable()->after('gclid')->index();
            $table->text('user_agent')->nullable()->after('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['ip_address']);
            $table->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
