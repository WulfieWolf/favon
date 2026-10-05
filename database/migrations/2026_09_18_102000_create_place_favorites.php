<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_favorites', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->boolean('notify_changes')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'place_id']);
            $table->index(['place_id', 'notify_changes']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_favorites');
    }
};
