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
        Schema::table('leads', function (Blueprint $table): void {
            $table->text('movable_immovable_property')->nullable()->after('property_location');
        });

        Schema::table('lead_guarantors', function (Blueprint $table): void {
            $table->text('movable_immovable_property')->nullable()->after('property_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn('movable_immovable_property');
        });

        Schema::table('lead_guarantors', function (Blueprint $table): void {
            $table->dropColumn('movable_immovable_property');
        });
    }
};
