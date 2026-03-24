<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_available_for_lead_assignment')
                ->default(true)
                ->after('role')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_available_for_lead_assignment']);
            $table->dropColumn('is_available_for_lead_assignment');
        });
    }
};
