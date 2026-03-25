<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->unsignedSmallInteger('reminder_minutes_before')->nullable()->after('color');
            $table->dateTime('reminder_sent_at')->nullable()->after('reminder_minutes_before');

            $table->index('reminder_minutes_before');
            $table->index('reminder_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('calendar_events', function (Blueprint $table): void {
            $table->dropIndex(['reminder_minutes_before']);
            $table->dropIndex(['reminder_sent_at']);
            $table->dropColumn(['reminder_minutes_before', 'reminder_sent_at']);
        });
    }
};
