<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_contact_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_contact_id')->constrained('place_contacts')->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('internal_comment')->nullable();
            $table->timestamps();

            $table->unique(['place_contact_id', 'locale'], 'pct_contact_locale_unique');
            $table->index(['locale', 'is_active'], 'pct_locale_active_idx');
        });

        $contacts = DB::table('place_contacts')
            ->select(['id', 'label', 'created_at', 'updated_at'])
            ->whereNotNull('label')
            ->where('label', '<>', '')
            ->get();

        foreach ($contacts as $contact) {
            DB::table('place_contact_translations')->insert([
                'place_contact_id' => $contact->id,
                'locale' => 'de',
                'label' => $contact->label,
                'is_active' => true,
                'internal_comment' => 'Migrated from legacy place_contacts.label.',
                'created_at' => $contact->created_at ?? now(),
                'updated_at' => $contact->updated_at ?? now(),
            ]);
        }

        Schema::table('place_contacts', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }

    public function down(): void
    {
        Schema::table('place_contacts', function (Blueprint $table) {
            $table->string('label')->nullable()->after('value');
        });

        $translations = DB::table('place_contact_translations')
            ->where('locale', 'de')
            ->where('is_active', true)
            ->get();

        foreach ($translations as $translation) {
            DB::table('place_contacts')
                ->where('id', $translation->place_contact_id)
                ->update(['label' => $translation->label]);
        }

        Schema::dropIfExists('place_contact_translations');
    }
};
