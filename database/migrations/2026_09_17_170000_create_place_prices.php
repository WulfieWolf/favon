<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 64)->unique();
            $table->unsignedInteger('sort_order')->default(10);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_searchable')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->index(['is_active', 'sort_order'], 'price_types_active_sort_idx');
        });

        Schema::create('place_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained('places')->cascadeOnDelete();
            $table->foreignId('price_type_id')->constrained('price_types')->restrictOnDelete();
            $table->decimal('amount', 14, 4)->nullable();
            $table->foreignId('currency_unit_id')->nullable()->constrained('units')->restrictOnDelete();
            $table->decimal('rate_quantity', 14, 4)->nullable();
            $table->foreignId('rate_unit_id')->nullable()->constrained('units')->restrictOnDelete();
            $table->boolean('is_included')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['place_id', 'price_type_id', 'is_active'], 'place_prices_type_active_idx');
            $table->index(['place_id', 'valid_from', 'valid_until'], 'place_prices_validity_idx');
            $table->index(['currency_unit_id', 'rate_unit_id'], 'place_prices_units_idx');
        });

        Schema::create('place_price_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_price_id')->constrained('place_prices')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('label')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['place_price_id', 'locale'], 'place_price_translations_unique');
            $table->index(['locale', 'is_active'], 'place_price_translations_locale_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_price_translations');
        Schema::dropIfExists('place_prices');
        Schema::dropIfExists('price_types');
    }
};
