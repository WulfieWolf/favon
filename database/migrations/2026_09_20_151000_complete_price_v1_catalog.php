<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('price_products')->updateOrInsert(
            ['slug' => 'overnight'],
            [
                'sort_order' => 5,
                'supports_vehicle_length' => false,
                'supports_age_range' => false,
                'is_active' => true,
                'is_searchable' => true,
                'internal_comment' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $overnightId = DB::table('price_products')->where('slug', 'overnight')->value('id');
        $this->translation('price_product', $overnightId, 'de', 'Übernachtungspauschale');
        $this->translation('price_product', $overnightId, 'en', 'Overnight fee');

        $products = DB::table('price_products')->where('is_active', true)->get(['id']);

        foreach ($products as $product) {
            DB::table('price_product_variants')->updateOrInsert(
                [
                    'price_product_id' => $product->id,
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
                ->where('price_product_id', $product->id)
                ->where('slug', 'other')
                ->value('id');

            $this->translation('price_product_variant', $variantId, 'de', 'Andere/s');
            $this->translation('price_product_variant', $variantId, 'en', 'Other');
        }
    }

    public function down(): void
    {
        $overnightId = DB::table('price_products')->where('slug', 'overnight')->value('id');

        if ($overnightId) {
            DB::table('price_product_variants')->where('price_product_id', $overnightId)->delete();
            DB::table('translations')
                ->where('entity_type', 'price_product')
                ->where('entity_id', $overnightId)
                ->delete();
            DB::table('price_products')->where('id', $overnightId)->delete();
        }
    }

    private function translation(string $entityType, int $entityId, string $locale, string $value): void
    {
        DB::table('translations')->updateOrInsert(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
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
};
