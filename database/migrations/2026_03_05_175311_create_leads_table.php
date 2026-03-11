<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();

            $table->string('credit_type')->index(); // consumer|mortgage
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone')->index();
            $table->string('email');
            $table->string('city');
            $table->unsignedInteger('amount');

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
