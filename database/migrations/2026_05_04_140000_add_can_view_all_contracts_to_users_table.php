<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('can_view_all_contracts')
                ->default(false)
                ->after('role');
            $table->index('can_view_all_contracts');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['can_view_all_contracts']);
            $table->dropColumn('can_view_all_contracts');
        });
    }
};
