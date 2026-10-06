<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_type', 32);
            $table->string('action', 100);
            $table->text('summary');
            $table->json('metadata')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['place_id', 'created_at']);
            $table->index(['actor_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_history');
    }
};
