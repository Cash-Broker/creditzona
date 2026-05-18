<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_guarantors', function (Blueprint $table): void {
            $table->string('workplace_tenure', 120)->nullable()->after('workplace');
        });
    }

    public function down(): void
    {
        Schema::table('lead_guarantors', function (Blueprint $table): void {
            $table->dropColumn('workplace_tenure');
        });
    }
};
