<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->timestamp('marked_for_later_at')
                ->nullable()
                ->after('returned_to_primary_at')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['marked_for_later_at']);
            $table->dropColumn('marked_for_later_at');
        });
    }
};
