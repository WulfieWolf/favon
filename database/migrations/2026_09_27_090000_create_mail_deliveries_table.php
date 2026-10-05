<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mail_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mail_type', 80);
            $table->string('recipient_hash', 64);
            $table->string('message_fingerprint', 64);
            $table->string('status', 24);
            $table->string('mailer', 40)->nullable();
            $table->string('locale', 12)->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('attempted_at');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['mail_type', 'attempted_at']);
            $table->index(['recipient_hash', 'attempted_at']);
            $table->index(['message_fingerprint', 'attempted_at']);
            $table->index(['status', 'attempted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mail_deliveries');
    }
};
