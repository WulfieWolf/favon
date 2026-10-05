<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('place_price_offers', function (Blueprint $table) {
            $table->unique('offer_uuid', 'ppo_offer_uuid_unique');
        });
    }

    public function down(): void
    {
        Schema::table('place_price_offers', function (Blueprint $table) {
            $table->dropUnique('ppo_offer_uuid_unique');
        });
    }
};
