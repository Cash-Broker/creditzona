<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            // Basic
            $table->string('full_name');
            $table->string('phone')->index();
            $table->string('email')->nullable();
            $table->string('city')->nullable();

            // Service
            $table->string('service_type')->index(); // consumer|mortgage|refinance|debt_buyout
            $table->unsignedInteger('amount')->nullable();
            $table->unsignedInteger('term_months')->nullable();

            // Sensitive (encrypted via casts)
            $table->text('egn')->nullable();
            $table->unsignedInteger('monthly_income')->nullable();
            $table->string('employment_type')->nullable(); // contract|self_employed|pensioner|unemployed
            $table->unsignedInteger('monthly_debt')->nullable();

            // Tracking
            $table->string('source')->nullable(); // organic|fb|google|referral
            $table->string('utm_source')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('gclid')->nullable();

            // Workflow
            $table->string('status')->default('new')->index(); // new|contacted|waiting_docs|submitted|approved|rejected|done|lost
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('priority')->default(2); // 1 high, 2 normal, 3 low

            // GDPR
            $table->timestamp('consent_at')->nullable();
            $table->string('consent_ip')->nullable();
            $table->string('consent_user_agent')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};