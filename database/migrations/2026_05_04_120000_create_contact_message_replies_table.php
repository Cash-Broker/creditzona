<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_message_replies', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('contact_message_id')
                ->constrained('contact_messages')
                ->cascadeOnDelete();
            $table->foreignId('sender_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('body');
            $table->string('from_email', 191);
            $table->string('to_email', 191);
            $table->string('subject', 255);
            $table->string('message_id', 191)->nullable();
            $table->string('in_reply_to', 191)->nullable();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['contact_message_id', 'sent_at']);
            $table->index('sender_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_message_replies');
    }
};
