<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('price_products')->updateOrInsert(
            ['slug' => 'pitch'],
            [
                'sort_order' => 8,
                'supports_vehicle_length' => true,
                'supports_age_range' => false,
                'is_active' => true,
                'is_searchable' => true,
                'internal_comment' => 'Generic pitch product for places without separate pitch categories.',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $productId = DB::table('price_products')->where('slug', 'pitch')->value('id');

        foreach ([
            'de' => 'Stellplatz',
            'en' => 'Pitch',
        ] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                [
                    'entity_type' => 'price_product',
                    'entity_id' => $productId,
                    'locale' => $locale,
                    'field' => 'name',
                ],
                [
                    'value' => $value,
                    'is_active' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        DB::table('price_product_variants')->updateOrInsert(
            [
                'price_product_id' => $productId,
                'slug' => 'other',
            ],
            [
                'sort_order' => 1000,
                'is_active' => true,
                'internal_comment' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $variantId = DB::table('price_product_variants')
            ->where('price_product_id', $productId)
            ->where('slug', 'other')
            ->value('id');

        foreach ([
            'de' => 'Andere/s',
            'en' => 'Other',
        ] as $locale => $value) {
            DB::table('translations')->updateOrInsert(
                [
                    'entity_type' => 'price_product_variant',
                    'entity_id' => $variantId,
                    'locale' => $locale,
                    'field' => 'name',
                ],
                [
                    'value' => $value,
                    'is_active' => true,
                    'internal_comment' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        $productId = DB::table('price_products')->where('slug', 'pitch')->value('id');

        if (! $productId) {
            return;
        }

        $variantIds = DB::table('price_product_variants')
            ->where('price_product_id', $productId)
            ->pluck('id');

        DB::table('translations')
            ->where('entity_type', 'price_product_variant')
            ->whereIn('entity_id', $variantIds)
            ->delete();

        DB::table('price_product_variants')
            ->where('price_product_id', $productId)
            ->delete();

        DB::table('translations')
            ->where('entity_type', 'price_product')
            ->where('entity_id', $productId)
            ->delete();

        DB::table('price_products')->where('id', $productId)->delete();
    }
};
