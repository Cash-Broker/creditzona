<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('credit_type')->index(); // consumer|mortgage
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->text('egn')->nullable();
            $table->string('phone')->index();
            $table->string('email');
            $table->string('city');
            $table->string('workplace')->nullable();
            $table->string('job_title')->nullable();
            $table->unsignedInteger('salary')->nullable();
            $table->string('marital_status')->nullable();
            $table->unsignedTinyInteger('children_under_18')->nullable();
            $table->string('salary_bank')->nullable();
            $table->string('credit_bank')->nullable();
            $table->json('documents')->nullable();
            $table->json('document_file_names')->nullable();
            $table->longText('internal_notes')->nullable();
            $table->unsignedInteger('amount');

            // Mortgage only
            $table->string('property_type')->nullable(); // house|apartment
            $table->string('property_location')->nullable();

            $table->string('source')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('gclid')->nullable();

            $table->string('status')->default('new')->index();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
