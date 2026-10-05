<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_article_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('support_article_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('title');
            $table->string('summary', 500)->nullable();
            $table->longText('body');
            $table->timestamps();
            $table->unique(['support_article_id', 'locale'], 'support_article_locale_unique');
            $table->index(['locale', 'support_article_id'], 'support_article_locale_idx');
        });

        Schema::create('public_support_entry_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('public_support_entry_id');
            $table->foreign('public_support_entry_id', 'pse_translations_entry_fk')
                ->references('id')
                ->on('public_support_entries')
                ->cascadeOnDelete();
            $table->string('locale', 16);
            $table->string('title');
            $table->longText('description')->nullable();
            $table->timestamps();
            $table->unique(['public_support_entry_id', 'locale'], 'public_support_entry_locale_unique');
            $table->index(['locale', 'public_support_entry_id'], 'public_support_entry_locale_idx');
        });

        $now = now();

        DB::table('support_articles')
            ->orderBy('id')
            ->each(function (object $article) use ($now): void {
                DB::table('support_article_translations')->insert([
                    'support_article_id' => $article->id,
                    'locale' => 'de',
                    'title' => $article->title,
                    'summary' => $article->summary,
                    'body' => $article->body,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        DB::table('public_support_entries')
            ->orderBy('id')
            ->each(function (object $entry) use ($now): void {
                DB::table('public_support_entry_translations')->insert([
                    'public_support_entry_id' => $entry->id,
                    'locale' => 'de',
                    'title' => $entry->title,
                    'description' => $entry->description,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        $englishArticles = require lang_path('en/help_articles.php');

        foreach ($englishArticles as $slug => $translation) {
            $articleId = DB::table('support_articles')->where('slug', $slug)->value('id');
            if (! $articleId) {
                continue;
            }

            DB::table('support_article_translations')->updateOrInsert(
                ['support_article_id' => $articleId, 'locale' => 'en'],
                [
                    'title' => $translation['title'],
                    'summary' => $translation['summary'] ?? null,
                    'body' => $translation['body'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('public_support_entry_translations');
        Schema::dropIfExists('support_article_translations');
    }
};
