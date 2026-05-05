<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('email', 'elena@creditzona.bg')
            ->update(['can_view_all_contracts' => true]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('email', 'elena@creditzona.bg')
            ->update(['can_view_all_contracts' => false]);
    }
};
