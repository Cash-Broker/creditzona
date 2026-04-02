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
        Schema::table('leads', function (Blueprint $table) {
            $table->string('city', 120)->nullable()->change();
        });

        DB::table('leads')->where('city', '')->update(['city' => null]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('leads')->whereNull('city')->update(['city' => '']);

        Schema::table('leads', function (Blueprint $table) {
            $table->string('city')->nullable(false)->change();
        });
    }
};
