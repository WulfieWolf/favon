<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('last_seen_at')->nullable()->after('remember_token')->index();
        });

        Schema::create('user_notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 80)->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('title');
            $table->text('message');
            $table->string('url', 2048)->nullable();
            $table->string('icon', 80)->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('available_at')->useCurrent()->index();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'available_at']);
        });

        Schema::create('user_notification_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_id')->constrained('user_notifications')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at');
            $table->timestamps();

            $table->unique(['notification_id', 'user_id']);
            $table->index(['user_id', 'read_at']);
        });

        Schema::create('notification_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 80)->index();
            $table->string('cluster_key', 120)->index();
            $table->string('priority', 20)->default('normal')->index();
            $table->string('title');
            $table->text('message');
            $table->string('url', 2048)->nullable();
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamp('processed_at')->nullable()->index();
            $table->foreignId('notification_id')->nullable()->constrained('user_notifications')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'cluster_key', 'processed_at'], 'notification_events_cluster_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_events');
        Schema::dropIfExists('user_notification_reads');
        Schema::dropIfExists('user_notifications');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['last_seen_at']);
            $table->dropColumn('last_seen_at');
        });
    }
};
