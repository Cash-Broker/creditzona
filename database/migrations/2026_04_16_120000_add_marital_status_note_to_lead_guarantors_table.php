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
        Schema::table('lead_guarantors', function (Blueprint $table) {
            $table->string('marital_status_note', 500)->nullable()->after('marital_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_guarantors', function (Blueprint $table) {
            $table->dropColumn('marital_status_note');
        });
    }
};
