<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $table): void {
            $table->id();
            $table->string('event_type', 80);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route_name', 160)->nullable();
            $table->string('source_ip_hash', 64)->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['event_type', 'created_at'], 'security_events_type_time_idx');
            $table->index(['source_ip_hash', 'created_at'], 'security_events_ip_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
