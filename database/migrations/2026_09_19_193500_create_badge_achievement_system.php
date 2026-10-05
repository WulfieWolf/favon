<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('badge_definitions', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('type', 30)->index();
            $table->string('name', 120);
            $table->string('description', 500);
            $table->string('icon', 80)->default('award');
            $table->boolean('is_hidden')->default(false);
            $table->integer('xp_reward')->default(0);
            $table->string('progress_metric', 80)->nullable();
            $table->unsignedInteger('target_value')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['type', 'is_active', 'sort_order']);
        });

        Schema::create('badge_tiers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('badge_id')->constrained('badge_definitions')->cascadeOnDelete();
            $table->string('tier', 30);
            $table->unsignedInteger('threshold');
            $table->unsignedInteger('sort_order');
            $table->timestamps();

            $table->unique(['badge_id', 'tier']);
            $table->unique(['badge_id', 'threshold']);
        });

        Schema::create('badge_progress_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('badge_definitions')->cascadeOnDelete();
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->string('contribution_key', 191);
            $table->boolean('is_active')->default(true);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['user_id', 'badge_id', 'contribution_key'], 'badge_progress_unique');
            $table->index(['user_id', 'badge_id', 'is_active'], 'badge_progress_count_idx');
            $table->index(['place_id', 'badge_id']);
        });

        Schema::create('user_badge_unlocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained('badge_definitions')->cascadeOnDelete();
            $table->string('tier', 30)->nullable();
            $table->string('unlock_key', 191)->unique();
            $table->string('award_comment', 1000)->nullable();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('unlocked_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'revoked_at', 'unlocked_at'], 'user_badges_active_idx');
            $table->index(['badge_id', 'revoked_at'], 'badge_rarity_idx');
        });

        Schema::create('user_activity_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('activity_date');
            $table->string('source', 60)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'activity_date']);
            $table->index(['user_id', 'activity_date']);
        });

        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->foreignId('selected_badge_id')
                ->nullable()
                ->after('public_handle')
                ->constrained('badge_definitions')
                ->nullOnDelete();
        });

        $now = now();

        $insertBadge = static function (array $values) use ($now): int {
            return (int) DB::table('badge_definitions')->insertGetId($values + [
                'icon' => 'award',
                'is_hidden' => false,
                'xp_reward' => 0,
                'progress_metric' => null,
                'target_value' => null,
                'sort_order' => 0,
                'is_active' => true,
                'metadata' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        };

        $progressBadges = [
            ['explorer', 'Entdecker', 'Neue Plätze erfolgreich zur Camperwolf-Karte beigetragen.', 'compass', [5, 25, 100, 250], 10],
            ['pathfinder', 'Pfadfinder', 'Neue Informationen beim Anlegen von Plätzen beigetragen.', 'route', [50, 200, 1500, 3000], 20],
            ['connoisseur', 'Kenner', 'Rezensionen zu Plätzen veröffentlicht.', 'message-star', [5, 25, 100, 300], 30],
            ['photographer', 'Fotograf', 'Akzeptierte Fotos zu Plätzen beigetragen.', 'camera', [10, 50, 250, 1000], 40],
            ['sleuth', 'Spürnase', 'Veröffentlichte Plätze sinnvoll ergänzt oder korrigiert.', 'zoom-check', [10, 50, 200, 750], 50],
            ['community-helper', 'Communityhelfer', 'Gültige Community-Prüfungen und Abstimmungen abgeschlossen.', 'users-group', [25, 100, 500, 2000], 60],
        ];

        foreach ($progressBadges as [$slug, $name, $description, $icon, $thresholds, $sortOrder]) {
            $badgeId = $insertBadge([
                'slug' => $slug,
                'type' => 'progress',
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'sort_order' => $sortOrder,
            ]);

            foreach (['bronze', 'silver', 'gold', 'platinum'] as $index => $tier) {
                DB::table('badge_tiers')->insert([
                    'badge_id' => $badgeId,
                    'tier' => $tier,
                    'threshold' => $thresholds[$index],
                    'sort_order' => ($index + 1) * 10,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $achievements = [
            ['first-steps', 'Erste Schritte', 'Die erste sinnvolle Änderung oder Ergänzung wurde veröffentlicht.', 'sparkles', 0, null, null, 110],
            ['first-find', 'Erster Fund', 'Der erste neu angelegte Platz wurde veröffentlicht.', 'map-pin-plus', 0, null, null, 120],
            ['first-opinion', 'Erste Meinung', 'Die erste Rezension wurde veröffentlicht.', 'message', 0, null, null, 130],
            ['first-photo', 'Erstes Bild', 'Das erste Foto wurde akzeptiert.', 'photo', 0, null, null, 140],
            ['streak-7', '7 Tage dabei', 'An 7 Tagen in Folge sinnvoll auf Camperwolf aktiv gewesen.', 'calendar-stats', 5, 'activity_streak', 7, 150],
            ['streak-30', '30 Tage dabei', 'An 30 Tagen in Folge sinnvoll auf Camperwolf aktiv gewesen.', 'calendar-month', 15, 'activity_streak', 30, 160],
            ['month-loyal', 'Monatstreu', 'In 12 aufeinanderfolgenden Monaten mindestens einmal sinnvoll aktiv gewesen.', 'calendar-heart', 25, 'active_month_streak', 12, 170],
            ['one-year', 'Ein Jahr Camperwolf', 'Seit mindestens einem Jahr Mitglied bei Camperwolf.', 'cake', 10, 'account_age_days', 365, 180],
            ['border-crosser', 'Grenzgänger', 'Sinnvolle Beiträge in 3 verschiedenen Ländern veröffentlicht.', 'world-pin', 10, 'countries_contributed', 3, 190],
            ['globetrotter', 'Weltenbummler', 'Sinnvolle Beiträge in 10 verschiedenen Ländern veröffentlicht.', 'world', 25, 'countries_contributed', 10, 200],
            ['eagle-eye', 'Adlerauge', 'Die erste bestätigte Korrektur an einem veröffentlichten Platz beigetragen.', 'eye-check', 0, null, null, 220],
            ['level-10', 'Level 10', 'Level 10 erreicht.', 'chevrons-up', 0, 'level', 10, 230],
            ['level-25', 'Level 25', 'Level 25 erreicht.', 'chevrons-up', 0, 'level', 25, 240],
            ['level-50', 'Level 50', 'Level 50 erreicht.', 'chevrons-up', 0, 'level', 50, 250],
            ['level-100', 'Level 100', 'Level 100 erreicht.', 'chevrons-up', 0, 'level', 100, 260],
            ['og-10', 'OG10 Member', 'Unter den ersten 10 registrierten Camperwolf-Mitgliedern.', 'crown', 0, 'registration_rank', 10, 270],
            ['og-50', 'OG50 Member', 'Unter den ersten 50 registrierten Camperwolf-Mitgliedern.', 'crown', 0, 'registration_rank', 50, 280],
            ['og-500', 'OG500 Member', 'Unter den ersten 500 registrierten Camperwolf-Mitgliedern.', 'crown', 0, 'registration_rank', 500, 290],
            ['og-1000', 'OG1000 Member', 'Unter den ersten 1000 registrierten Camperwolf-Mitgliedern.', 'crown', 0, 'registration_rank', 1000, 300],
        ];

        foreach ($achievements as [$slug, $name, $description, $icon, $xp, $metric, $target, $sortOrder]) {
            $insertBadge([
                'slug' => $slug,
                'type' => 'achievement',
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'xp_reward' => $xp,
                'progress_metric' => $metric,
                'target_value' => $target,
                'sort_order' => $sortOrder,
            ]);
        }

        $manualBadges = [
            ['betatester-2026', 'Betatester 2026', 'Hat Camperwolf während der Betaphase 2026 aktiv unterstützt.', 'flask', 25, 410],
            ['camperwolf-treffen-2026', 'Camperwolf-Treffen 2026', 'War beim Camperwolf-Treffen 2026 dabei.', 'campfire', 15, 420],
            ['wulfie-getroffen', 'Wulfie getroffen', 'Hat Wulfie unterwegs auf einem Platz getroffen und Hallo gesagt.', 'paw', 25, 430],
            ['besonderer-dank', 'Besonderer Dank', 'Wird für besondere Unterstützung des Camperwolf-Projekts vergeben.', 'heart-handshake', 25, 440],
            ['frueher-unterstuetzer', 'Früher Unterstützer', 'Hat Camperwolf bereits in einer frühen Projektphase unterstützt.', 'seedling', 10, 450],
        ];

        foreach ($manualBadges as [$slug, $name, $description, $icon, $xp, $sortOrder]) {
            $insertBadge([
                'slug' => $slug,
                'type' => 'manual',
                'name' => $name,
                'description' => $description,
                'icon' => $icon,
                'xp_reward' => $xp,
                'sort_order' => $sortOrder,
            ]);
        }

        DB::table('support_articles')->updateOrInsert(
            ['slug' => 'badges-und-achievements'],
            [
                'title' => 'Badges & Achievements',
                'summary' => 'Fortschritts-Badges, Achievements, Auszeichnungen und Profil-Titel bei Camperwolf.',
                'body' => <<<'TEXT'
Fortschritts-Badges
Camperwolf kennt sechs fortlaufende Hauptbadges: Entdecker, Pfadfinder, Kenner, Fotograf, Spürnase und Communityhelfer. Jeder Badge hat die Stufen Bronze, Silber, Gold und Platin. Nach Platin läuft der sichtbare Zähler weiter.

Dieselbe Information kann für denselben Nutzer nicht durch wiederholtes Ändern mehrfach gezählt werden. Pfadfinder zählt Informationen, die beim Anlegen eines neuen Platzes beigetragen werden. Spürnase zählt spätere Ergänzungen oder Korrekturen an bereits veröffentlichten Plätzen. Bereits selbst beigetragene Informationen zählen beim späteren Ändern nicht erneut.

Beim Fotografen zählen höchstens fünf aktive akzeptierte Fotos pro Nutzer und Platz. Wird ein zählendes Foto gelöscht, sinkt der aktuelle Fotograf-Zähler entsprechend. Bereits erreichte Badge-Stufen bleiben als historischer Erfolg erhalten.

Achievements
Achievements sind einmalige Erfolge. Sichtbare Achievements können schon vor dem Freischalten mit ihrer Bedingung und, wenn sinnvoll, ihrem Fortschritt angezeigt werden. Manche Achievements vergeben zusätzlich einen kleinen festen XP-Bonus; dieser erscheint im XP-Verlauf.

Versteckte Achievements werden vor dem Freischalten nicht verraten. Andere Nutzer sehen auch nach dem Fund nicht, welches versteckte Achievement entdeckt wurde, sondern nur den Gesamtstand wie „3 von 12 entdeckt“.

Auszeichnungen
Manuelle Badges werden durch berechtigte Administratoren vergeben, zum Beispiel für Betatests, Treffen oder besondere Hilfe. Eine Auszeichnung kann einen individuellen öffentlichen Vergabe-Kommentar und einen fest definierten XP-Wert besitzen.

Profil-Titel
Aus bereits freigeschalteten sichtbaren Badges, Achievements und Auszeichnungen kann ein Nutzer einen Profil-Titel auswählen. Versteckte Achievements können nicht als öffentlicher Titel verwendet werden.

Seltenheit
Bei Achievements und Auszeichnungen zeigt Camperwolf an, welcher Anteil der registrierten Nutzer den jeweiligen Badge besitzt.
TEXT,
                'context_key' => 'gamification',
                'sort_order' => 31,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        );
    }

    public function down(): void
    {
        DB::table('support_articles')->where('slug', 'badges-und-achievements')->delete();

        Schema::table('user_profiles', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('selected_badge_id');
        });

        Schema::dropIfExists('user_activity_days');
        Schema::dropIfExists('user_badge_unlocks');
        Schema::dropIfExists('badge_progress_events');
        Schema::dropIfExists('badge_tiers');
        Schema::dropIfExists('badge_definitions');
    }
};
