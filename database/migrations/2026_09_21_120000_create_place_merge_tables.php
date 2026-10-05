<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_merges', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_place_id')->constrained('places')->restrictOnDelete();
            $table->foreignId('target_place_id')->constrained('places')->restrictOnDelete();
            $table->foreignId('merged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('completed');
            $table->json('decisions');
            $table->json('snapshot');
            $table->timestamp('merged_at');
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->timestamps();

            $table->index('source_place_id');
            $table->index(['target_place_id', 'status']);
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('place_merges');
    }
};
