<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abuse_flags', function (Blueprint $table): void {
            $table->id();
            $table->string('entity_type', 60);
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('rule_code', 80);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 20)->default('open');
            $table->string('source_ip_hash', 64)->nullable();
            $table->json('context')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['entity_type', 'entity_id', 'rule_code'], 'abuse_flags_entity_rule_unique');
            $table->index(['status', 'entity_type', 'created_at'], 'abuse_flags_queue_idx');
            $table->index(['user_id', 'status', 'created_at'], 'abuse_flags_user_idx');
            $table->index(['source_ip_hash', 'status', 'created_at'], 'abuse_flags_ip_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abuse_flags');
    }
};
