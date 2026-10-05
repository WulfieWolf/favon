<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_ticket_privacy_cases', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->unique()->constrained('support_tickets')->cascadeOnDelete();
            $table->string('identity_status', 30)->default('pending')->index();
            $table->string('deadline_rule', 30)->default('manual')->index();
            $table->timestamp('received_at')->index();
            $table->timestamp('original_due_at')->nullable()->index();
            $table->timestamp('due_at')->nullable()->index();
            $table->text('extension_reason')->nullable();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_privacy_cases');
    }
};
