<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_settings', function (Blueprint $table): void {
            $table->boolean('show_gamification')->default(true);
        });

        Schema::create('xp_ledger', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('event_type', 60)->index();
            $table->string('source_type', 60)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('place_id')->nullable()->constrained('places')->nullOnDelete();
            $table->string('action_key', 191)->nullable();
            $table->string('dedupe_key', 191)->nullable()->unique();
            $table->integer('xp');
            $table->string('description', 500);
            $table->string('rule_version', 30)->default('v1');
            $table->json('metadata')->nullable();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'place_id', 'event_type']);
            $table->index(['source_type', 'source_id']);
        });

        DB::table('user_settings')->update([
            'show_gamification' => true,
        ]);

        DB::table('support_articles')->updateOrInsert(
            ['slug' => 'level-und-xp'],
            [
                'title' => 'Level und XP',
                'summary' => 'So funktioniert das freiwillige Level- und XP-System von Camperwolf.',
                'body' => <<<'TEXT'
Wenn du Camperwolf mit Informationen, Bewertungen, Fotos oder anderen Beiträgen hilfst, kannst du dafür **Erfahrungspunkte (XP)** bekommen.

Mit den XP steigt nach und nach dein **Level**. Das zeigt, wie viel du bereits zur Community beigetragen hast.

Wichtig dabei: Ein höheres Level bringt **keine besonderen Rechte oder Vorteile**. Es beeinflusst auch keine Bewertungen oder die Reihenfolge von Plätzen.

## Wie bekomme ich XP?

XP bekommst du für hilfreiche Beiträge zu Camperwolf.

Wenn ein Beitrag erst von einem Moderator geprüft werden muss, werden die XP vergeben, sobald der Beitrag freigegeben wurde.

Aktuell bekommst du zum Beispiel:

- **+1 XP** für eine zusätzliche Information oder ein Merkmal zu einem Platz
- **+2 XP** für größere Texte, zum Beispiel eine Beschreibung oder Hinweise zur Anfahrt
- **+1 XP** für jede beantwortete Frage bei einer Bewertung
- **+1 XP** für ein Foto - maximal 5 Foto-XP pro Platz
- **+2 XP** für eine Rezension
- **+4 XP insgesamt** für eine ausführliche Rezension mit mindestens 300 Zeichen
- **+1 XP**, wenn jemand dein Foto als hilfreich bewertet - maximal 10 XP pro Foto

Die Angaben, die unbedingt benötigt werden, um einen neuen Platz anzulegen - zum Beispiel Name, Platztyp und Position - bringen keine zusätzlichen XP.

Wenn du freiwillig weitere Informationen zum neuen Platz einträgst, kannst du dafür aber XP bekommen.

## Kann ich für dieselbe Information mehrfach XP bekommen?

Nein. Für dieselbe Information zu einem Platz bekommst du normalerweise nur einmal XP.

Wenn du zum Beispiel ein Merkmal ergänzt und dafür XP erhalten hast, bekommst du nicht erneut XP, wenn du dieses Merkmal später selbst aktualisierst.

Ergänzt oder aktualisiert ein anderer Nutzer diese Information zum ersten Mal, kann dieser dafür ebenfalls XP bekommen.

## Was passiert, wenn ich etwas lösche und neu eintrage?

Bereits erhaltene XP lassen sich dadurch nicht erneut verdienen.

Ein Beispiel: Du hast für fünf Fotos eines Platzes bereits die maximalen 5 Foto-XP bekommen. Wenn du diese Fotos löschst und neue hochlädst, bekommst du dafür nicht noch einmal Foto-XP.

## Wie funktionieren die Level?

Du startest mit **Level 1 bei 0 XP**.

Die ersten Level kannst du relativ schnell erreichen. Mit jedem weiteren Level brauchst du mehr XP für den nächsten Aufstieg.

Deine bereits verdienten XP bleiben dabei erhalten.

## Wo sehe ich meine XP?

In deinem Profil kannst du deinen **XP-Verlauf** ansehen.

Dort siehst du, wann du XP bekommen hast und wofür. Auch Korrekturen werden dort angezeigt.

Zum Beispiel:

`01.01.2026, 20:32 - Ausführliche Rezension geschrieben: +4 XP`

`02.01.2026, 11:32 - Merkmal eines Platzes ergänzt: +1 XP`

## Was sind Sonder-XP und Korrekturen?

Manchmal kann es zusätzliche XP geben - zum Beispiel für besondere Community-Aktionen, Veranstaltungen oder Auszeichnungen.

Es kann auch vorkommen, dass bereits vergebene XP korrigiert werden müssen. Wird zum Beispiel ein Beitrag von einem Moderator gelöscht, für den du zuvor XP bekommen hast, können die dafür vergebenen XP wieder abgezogen werden.

Solche Änderungen erscheinen ebenfalls in deinem XP-Verlauf.

## Ich möchte das nicht. Kann ich Level und XP ausblenden?

Ja. In deinen Profileinstellungen kannst du festlegen, dass deine **Gamification nicht öffentlich angezeigt** wird.

Dann sehen andere Nutzer dein Level, deine Fortschrittsanzeige, deine Badges und deinen XP-Verlauf nicht mehr.

Deine XP gehen dadurch **nicht verloren**. Camperwolf zählt sie im Hintergrund weiter und dein Level kann weiterhin steigen.

Die Einstellung betrifft nur dein eigenes Profil. Level und Badges anderer Nutzer werden dir weiterhin angezeigt, sofern diese ihre Gamification öffentlich anzeigen.
TEXT,
                'context_key' => 'gamification',
                'sort_order' => 30,
                'is_active' => true,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    public function down(): void
    {
        DB::table('support_articles')->where('slug', 'level-und-xp')->delete();

        Schema::dropIfExists('xp_ledger');

        Schema::table('user_settings', function (Blueprint $table): void {
            $table->dropColumn('show_gamification');
        });
    }
};
