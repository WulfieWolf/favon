<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_articles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body');
            $table->string('context_key', 100)->nullable()->index();
            $table->unsignedInteger('sort_order')->default(100);
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('public_support_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 160)->unique();
            $table->string('type', 40)->index();
            $table->string('status', 40)->index();
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('context_key', 100)->nullable()->index();
            $table->boolean('is_public')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(100);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone', 80)->nullable();
            $table->string('type', 40)->index();
            $table->string('status', 40)->default('new')->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('subject')->nullable();
            $table->longText('description');
            $table->string('context_key', 100)->nullable()->index();
            $table->string('module', 100)->nullable();
            $table->string('route_name', 160)->nullable();
            $table->text('source_url')->nullable();
            $table->text('user_agent')->nullable();
            $table->foreignId('public_entry_id')->nullable()->constrained('public_support_entries')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->index();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });

        Schema::create('support_ticket_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('message_type', 30);
            $table->longText('message');
            $table->timestamps();
            $table->index(['ticket_id', 'created_at']);
        });

        Schema::create('support_ticket_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_events');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('public_support_entries');
        Schema::dropIfExists('support_articles');
    }
};
