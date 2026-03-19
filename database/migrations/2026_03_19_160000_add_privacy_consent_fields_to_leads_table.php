<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->boolean('privacy_consent_accepted')
                ->default(false)
                ->after('gclid');
            $table->timestamp('privacy_consent_accepted_at')
                ->nullable()
                ->after('privacy_consent_accepted');
            $table->string('privacy_consent_document_name')
                ->nullable()
                ->after('privacy_consent_accepted_at');
            $table->string('privacy_consent_document_path')
                ->nullable()
                ->after('privacy_consent_document_name');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'privacy_consent_accepted',
                'privacy_consent_accepted_at',
                'privacy_consent_document_name',
                'privacy_consent_document_path',
            ]);
        });
    }
};
