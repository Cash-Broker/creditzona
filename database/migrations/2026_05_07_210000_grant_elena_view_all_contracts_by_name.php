<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Re-grant can_view_all_contracts to every operator whose name starts
        // with "Елена" — covers cases where the email used in production
        // differs from the demo seed (creditzona.bg / creditzona.test).
        DB::table('users')
            ->where('role', User::ROLE_OPERATOR)
            ->where('name', 'like', 'Елена%')
            ->update(['can_view_all_contracts' => true]);
    }

    public function down(): void
    {
        // No-op: revoking via the email-based migration is preserved separately.
    }
};
