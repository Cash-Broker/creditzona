<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_messages', function (Blueprint $table) {
            $table->foreignId('guarantor_id')->nullable()->after('user_id')
                ->constrained('lead_guarantors')->nullOnDelete();
            $table->index(['lead_id', 'guarantor_id']);
        });
    }

    public function down(): void
    {
        Schema::table('lead_messages', function (Blueprint $table) {
            $table->dropForeign(['guarantor_id']);
            $table->dropIndex(['lead_id', 'guarantor_id']);
            $table->dropColumn('guarantor_id');
        });
    }
};
