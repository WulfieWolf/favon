<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->string('operator_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('place_id', 'place_details_place_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_details');
    }
};
