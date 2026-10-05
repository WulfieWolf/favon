<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $roles = [
            ['slug' => 'guest', 'name' => 'Gast', 'sort_order' => 10, 'is_system' => true],
            ['slug' => 'user', 'name' => 'User', 'sort_order' => 20, 'is_system' => true],
            ['slug' => 'mod', 'name' => 'Moderator', 'sort_order' => 30, 'is_system' => true],
            ['slug' => 'admin', 'name' => 'Administrator', 'sort_order' => 40, 'is_system' => true],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['slug' => $role['slug']],
                $role + ['is_active' => true, 'updated_at' => $now, 'created_at' => $now],
            );
        }

        $permissions = [
            ['places', 'places.view', 'Plätze ansehen', 'Öffentlich sichtbare Plätze ansehen.'],
            ['places', 'places.suggest', 'Plätze vorschlagen', 'Neue Plätze zur Prüfung vorschlagen.'],
            ['places', 'places.view_unpublished', 'Unveröffentlichte Plätze ansehen', 'Noch nicht veröffentlichte Plätze sehen.'],
            ['places', 'places.edit_own_suggestion', 'Eigene Platzvorschläge bearbeiten', 'Eigene noch offene Vorschläge bearbeiten.'],
            ['places', 'places.edit', 'Plätze direkt bearbeiten', 'Bestehende Plätze ohne Vorschlagsworkflow direkt ändern.'],
            ['places', 'places.create_direct', 'Plätze direkt anlegen', 'Plätze ohne Freigabeworkflow anlegen.'],
            ['places', 'places.deactivate', 'Plätze deaktivieren', 'Plätze redaktionell aus dem aktiven Listing nehmen.'],
            ['places', 'places.restore', 'Plätze wiederherstellen', 'Redaktionell deaktivierte Plätze wieder aktivieren.'],
            ['places', 'places.approve_changes', 'Platzänderungen freigeben', 'Änderungsvorschläge genehmigen oder ablehnen.'],
            ['places', 'places.merge', 'Plätze zusammenführen', 'Duplikate in einen Zielplatz zusammenführen.'],
            ['places', 'places.delete_permanently', 'Plätze endgültig löschen', 'Platzdaten vollständig entfernen und nur einen internen Tombstone behalten.'],
            ['places', 'places.change_publication_status', 'Publikationsstatus ändern', 'Den redaktionellen Veröffentlichungsstatus eines Platzes ändern.'],
            ['places', 'places.change_legal_status', 'Rechtsstatus ändern', 'Den rechtlichen Nutzungsstatus eines Platzes ändern.'],

            ['reviews', 'reviews.create', 'Bewertungen erstellen', 'Eigene Bewertungen abgeben.'],
            ['reviews', 'reviews.edit_own', 'Eigene Bewertungen bearbeiten', 'Eigene Bewertungen bearbeiten.'],
            ['reviews', 'reviews.delete_own', 'Eigene Bewertungen löschen', 'Eigene Bewertungen deaktivieren.'],
            ['reviews', 'reviews.view_pending', 'Offene Bewertungen ansehen', 'Noch nicht freigegebene Bewertungen sehen.'],
            ['reviews', 'reviews.moderate', 'Bewertungen moderieren', 'Bewertungen freigeben oder ablehnen.'],
            ['reviews', 'reviews.delete_any', 'Fremde Bewertungen entfernen', 'Fremde Bewertungen deaktivieren.'],
            ['reviews', 'reviews.restore', 'Bewertungen wiederherstellen', 'Entfernte Bewertungen wieder aktivieren.'],
            ['reviews', 'reviews.mark_verified_visit', 'Besuch verifizieren', 'Eine Bewertung als verifizierten Besuch markieren.'],
            ['reviews', 'reviews.vote_helpful', 'Bewertungen als hilfreich markieren', 'Eine Helpful-Stimme abgeben.'],
            ['reviews', 'reviews.remove_own_helpful_vote', 'Helpful-Stimme entfernen', 'Eigene Helpful-Stimme entfernen.'],

            ['photos', 'photos.upload', 'Fotos hochladen', 'Fotos zu Plätzen oder Bewertungen hochladen.'],
            ['photos', 'photos.delete_own', 'Eigene Fotos entfernen', 'Eigene Fotos deaktivieren.'],
            ['photos', 'photos.view_pending', 'Offene Fotos ansehen', 'Noch nicht freigegebene Fotos sehen.'],
            ['photos', 'photos.moderate', 'Fotos moderieren', 'Fotos freigeben oder ablehnen.'],
            ['photos', 'photos.delete_any', 'Fremde Fotos entfernen', 'Fremde Fotos deaktivieren.'],
            ['photos', 'photos.restore', 'Fotos wiederherstellen', 'Deaktivierte Fotos wiederherstellen.'],
            ['photos', 'photos.purge', 'Fotos endgültig löschen', 'Bilddateien physisch aus dem Storage löschen; DB-Historie bleibt erhalten.'],
            ['photos', 'photos.set_place_order', 'Foto-Reihenfolge ändern', 'Reihenfolge der Platzfotos ändern.'],
            ['photos', 'photos.set_cover', 'Titelbild festlegen', 'Das Hauptbild eines Platzes festlegen.'],

            ['users', 'users.view', 'Benutzerverzeichnis ansehen', 'Registrierte Benutzer finden und Basisdaten sehen.'],
            ['users', 'users.view_profile', 'Benutzerprofile ansehen', 'Öffentliche Profile anderer Community-Mitglieder öffnen.'],
            ['users', 'users.view_details', 'Benutzerdetails ansehen', 'Erweiterte Moderations- und Accountinformationen sehen.'],
            ['users', 'users.edit_profile', 'Fremde Profile bearbeiten', 'Profildaten anderer Benutzer administrativ ändern.'],
            ['users', 'users.verify_email', 'E-Mail manuell bestätigen', 'Eine Benutzer-E-Mail administrativ als bestätigt markieren.'],
            ['users', 'users.warn', 'Benutzer verwarnen', 'Moderationsverwarnungen aussprechen.'],
            ['users', 'users.suspend', 'Benutzer sperren', 'Benutzer zeitweise sperren.'],
            ['users', 'users.unsuspend', 'Sperre aufheben', 'Zeitweise Sperren aufheben.'],
            ['users', 'users.ban', 'Benutzer bannen', 'Benutzer unbefristet sperren.'],
            ['users', 'users.unban', 'Ban aufheben', 'Unbefristete Sperren aufheben.'],
            ['users', 'users.delete_account', 'Accounts administrativ deaktivieren', 'Benutzeraccounts administrativ deaktivieren bzw. löschen.'],
            ['users', 'users.assign_roles', 'Rollen zuweisen', 'Nicht-Admin-Rollen zuweisen oder entziehen.'],
            ['users', 'users.override_permissions', 'Benutzerrechte überschreiben', 'Individuelle Permission-Overrides setzen oder entfernen.'],
            ['users', 'users.manage_badges', 'Manuelle Auszeichnungen verwalten', 'Manuelle Badges und Auszeichnungen vergeben oder entziehen.'],

            ['reports', 'reports.create', 'Meldungen erstellen', 'Missbrauch oder problematische Inhalte melden.'],
            ['reports', 'reports.view_own', 'Eigene Meldungen ansehen', 'Eigene Meldungen und deren Status sehen.'],
            ['reports', 'reports.view_all', 'Alle Meldungen ansehen', 'Moderationswarteschlange aller Meldungen sehen.'],
            ['reports', 'reports.handle', 'Meldungen bearbeiten', 'Meldungen bearbeiten und abschließen.'],
            ['reports', 'reports.reopen', 'Meldungen wieder öffnen', 'Abgeschlossene Meldungen wieder öffnen.'],
            ['reports', 'reports.assign', 'Meldungen zuweisen', 'Meldungen Moderatoren oder Admins zuweisen.'],
            ['reports', 'reports.add_internal_note', 'Interne Notizen zu Meldungen', 'Interne Moderationsnotizen hinzufügen.'],

            ['audit', 'audit.view_own', 'Eigene Historie ansehen', 'Audit-Einträge ansehen, die den eigenen Account betreffen.'],
            ['audit', 'audit.view_moderation', 'Moderationshistorie ansehen', 'Moderations- und Abuse-Historie einsehen.'],
            ['audit', 'audit.view_all', 'Vollständige Audit-Logs ansehen', 'Alle Audit-Einträge einsehen.'],
            ['audit', 'audit.export', 'Audit-Logs exportieren', 'Audit-Logs exportieren.'],
            ['audit', 'audit.view_sensitive', 'Sensible Audit-Details ansehen', 'Sensible technische Audit-Metadaten sehen.'],

            ['roles', 'roles.view', 'Rollen ansehen', 'Rollen und deren Konfiguration ansehen.'],
            ['roles', 'roles.create', 'Rollen anlegen', 'Neue Rollen anlegen.'],
            ['roles', 'roles.edit', 'Rollen bearbeiten', 'Rollen ändern.'],
            ['roles', 'roles.deactivate', 'Rollen deaktivieren', 'Rollen deaktivieren.'],
            ['roles', 'role_permissions.manage', 'Rollenmatrix verwalten', 'Permissions für Rollen setzen oder entfernen.'],
            ['roles', 'permissions.view', 'Permissions ansehen', 'Permission-Katalog ansehen.'],
            ['roles', 'permissions.create', 'Permissions anlegen', 'Neue Permissions anlegen.'],
            ['roles', 'permissions.edit', 'Permissions bearbeiten', 'Permission-Metadaten ändern.'],
            ['roles', 'permissions.deactivate', 'Permissions deaktivieren', 'Permissions deaktivieren.'],

            ['owners', 'owners.request_verification', 'Betreiber-Verifizierung beantragen', 'Verifizierung als Betreiber eines Platzes beantragen.'],
            ['owners', 'owners.view_own_requests', 'Eigene Betreiberanträge ansehen', 'Eigene Verifizierungsanträge und deren Status sehen.'],
            ['owners', 'owners.view_all_requests', 'Alle Betreiberanträge ansehen', 'Alle Betreiber-Verifizierungsanträge sehen.'],
            ['owners', 'owners.verify', 'Betreiber verifizieren', 'Betreiber-Verifizierungen genehmigen.'],
            ['owners', 'owners.reject', 'Betreiberanträge ablehnen', 'Betreiber-Verifizierungsanträge ablehnen.'],
            ['owners', 'owners.revoke', 'Betreiberstatus entziehen', 'Eine bestehende Betreiber-Verifizierung widerrufen.'],

            ['community', 'favorites.manage_own', 'Eigene Favoriten verwalten', 'Eigene Platzfavoriten hinzufügen oder entfernen.'],
            ['community', 'owner_replies.moderate', 'Betreiberantworten moderieren', 'Betreiberantworten ausblenden oder freigeben.'],

            ['reference', 'reference.view', 'Stammdaten ansehen', 'Referenz- und Stammdaten im Adminbereich ansehen.'],
            ['reference', 'features.manage', 'Features verwalten', 'Features und Tags anlegen, ändern oder deaktivieren.'],
            ['reference', 'features.suggest', 'Features vorschlagen', 'Neue Features bzw. Tags vorschlagen.'],
            ['reference', 'features.view_suggestions', 'Feature-Vorschläge ansehen', 'Vorschläge für neue Features sehen.'],
            ['reference', 'features.approve_suggestions', 'Feature-Vorschläge freigeben', 'Feature-Vorschläge genehmigen oder ablehnen.'],
            ['reference', 'feature_categories.manage', 'Feature-Kategorien verwalten', 'Feature-Kategorien verwalten.'],
            ['reference', 'feature_options.manage', 'Feature-Optionen verwalten', 'Auswahloptionen von Features verwalten.'],
            ['reference', 'units.manage', 'Einheiten verwalten', 'Einheiten und Einheitentypen verwalten.'],
            ['reference', 'price_types.manage', 'Preisarten verwalten', 'Preisarten verwalten.'],
            ['reference', 'vehicle_types.manage', 'Fahrzeugtypen verwalten', 'Fahrzeugtypen verwalten.'],
            ['reference', 'place_types.manage', 'Platztypen verwalten', 'Platztypen verwalten.'],
            ['reference', 'countries.manage', 'Länder verwalten', 'Länder verwalten.'],
            ['reference', 'regions.manage', 'Regionen verwalten', 'Regionen verwalten.'],
            ['reference', 'translations.manage', 'Übersetzungen verwalten', 'Zentrale Übersetzungen pflegen.'],
            ['reference', 'icons.manage', 'Icons verwalten', 'Icon-Katalog verwalten.'],
            ['reference', 'suggestable_fields.manage', 'Vorschlagbare Felder verwalten', 'Steuern, welche Felder vorgeschlagen werden dürfen.'],

            ['sources', 'sources.view_public', 'Öffentliche Quellen ansehen', 'Öffentliche Quellenhinweise zu Platzdaten sehen.'],
            ['sources', 'sources.add', 'Quellen hinzufügen', 'Quellen zu Platzdaten oder Vorschlägen hinzufügen.'],
            ['sources', 'sources.edit_own', 'Eigene Quellen bearbeiten', 'Eigene noch nicht abgeschlossene Quellenangaben korrigieren.'],
            ['sources', 'sources.view_internal', 'Interne Quellen ansehen', 'Interne Quellen und Quellenkommentare sehen.'],
            ['sources', 'sources.edit_any', 'Quellen verwalten', 'Fremde Quellenangaben administrativ bearbeiten.'],
            ['sources', 'sources.deactivate', 'Quellen deaktivieren', 'Quellen deaktivieren.'],
            ['sources', 'sources.restore', 'Quellen wiederherstellen', 'Deaktivierte Quellen wieder aktivieren.'],
            ['sources', 'history.view_place', 'Öffentlichen Platzverlauf ansehen', 'Öffentlichen Änderungsverlauf eines Platzes ansehen.'],
            ['sources', 'history.view_internal', 'Internen Änderungsverlauf ansehen', 'Vollständigen internen Platzverlauf sehen.'],

            ['notifications', 'notifications.view_own', 'Eigene Benachrichtigungen ansehen', 'Eigene Benachrichtigungen sehen.'],
            ['notifications', 'notifications.manage_own', 'Eigene Benachrichtigungen verwalten', 'Eigene Benachrichtigungseinstellungen ändern.'],
            ['notifications', 'subscriptions.manage_own', 'Eigene Abos verwalten', 'Eigene Platz-Abonnements verwalten.'],
            ['notifications', 'notifications.send_system', 'Systemnachrichten senden', 'Systemweite Benachrichtigungen versenden.'],
            ['notifications', 'notifications.send_moderation', 'Moderationsnachrichten senden', 'Nachrichten im Rahmen eines Moderationsfalls senden.'],
            ['notifications', 'notifications.view_delivery_log', 'Versandhistorie ansehen', 'Versand- und Fehlerhistorie von Benachrichtigungen sehen.'],

            ['support', 'support.create', 'Supportmeldungen erstellen', 'Eigene Supportmeldungen erstellen.'],
            ['support', 'support.view_own', 'Eigene Supportmeldungen ansehen', 'Eigene Supportmeldungen und deren Verlauf ansehen.'],
            ['support', 'support.reply_own', 'Auf eigene Supportmeldungen antworten', 'Auf offene eigene Supportmeldungen antworten.'],
            ['support', 'support.view_all', 'Alle Supportmeldungen ansehen', 'Supportwarteschlange einschließlich Gastmeldungen ansehen.'],
            ['support', 'support.reply', 'Auf Supportmeldungen antworten', 'Öffentliche Supportantworten schreiben.'],
            ['support', 'support.internal_note', 'Interne Supportnotizen', 'Interne, für Nutzer unsichtbare Supportnotizen schreiben.'],
            ['support', 'support.change_status', 'Supportstatus ändern', 'Status und Priorität von Supportmeldungen ändern.'],
            ['support', 'support.assign', 'Supportmeldungen zuweisen', 'Supportmeldungen Mitarbeitern zuweisen.'],
            ['support', 'support.manage_content', 'Hilfe und Roadmap pflegen', 'Hilfeartikel, Known Bugs und Roadmap-Einträge redaktionell verwalten.'],

            ['features', 'features.manage_catalog', 'Merkmalskatalog verwalten', 'Kategorien, Merkmale, Workflows und Platztyp-Zuordnungen administrativ pflegen.'],

            ['security', 'security.view_own_sessions', 'Eigene Sessions ansehen', 'Eigene aktive Sessions sehen.'],
            ['security', 'security.revoke_own_sessions', 'Eigene Sessions beenden', 'Eigene Sessions beenden.'],
            ['security', 'security.manage_own_2fa', 'Eigene 2FA verwalten', 'Eigene Zwei-Faktor-Authentifizierung verwalten.'],
            ['security', 'security.view_user_sessions', 'Fremde Sessions ansehen', 'Sessions anderer Benutzer sehen.'],
            ['security', 'security.revoke_user_sessions', 'Fremde Sessions beenden', 'Sessions anderer Benutzer beenden.'],
            ['security', 'security.force_password_reset', 'Passwort-Reset erzwingen', 'Für einen Benutzer einen Passwort-Reset erzwingen.'],
            ['security', 'security.force_2fa', '2FA erzwingen', '2FA für Benutzer oder Rollen verpflichtend machen.'],
            ['security', 'security.view_login_history', 'Login-Historie ansehen', 'Login-Historie anderer Benutzer sehen.'],

            ['privacy', 'privacy.export_own_data', 'Eigene Daten exportieren', 'Export der eigenen personenbezogenen Daten anfordern.'],
            ['privacy', 'privacy.request_account_deletion', 'Account-Löschung beantragen', 'Löschung bzw. Deaktivierung des eigenen Accounts beantragen.'],

            ['statistics', 'statistics.view', 'Statistik ansehen', 'Aggregierte Bestands-, Aktivitäts- und Nutzungsstatistiken ansehen.'],

            ['system', 'settings.manage', 'Systemeinstellungen verwalten', 'Allgemeine Systemeinstellungen ändern.'],
            ['system', 'imports.run', 'Datenimporte ausführen', 'Administrativ Datenimporte starten.'],
            ['system', 'imports.view_history', 'Importhistorie ansehen', 'Importläufe und deren Ergebnis ansehen.'],
        ];

        $sort = 10;
        foreach ($permissions as [$category, $slug, $name, $description]) {
            DB::table('permissions')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'category' => $category,
                    'description' => $description,
                    'sort_order' => $sort,
                    'is_active' => true,
                    'updated_at' => $now,
                    'created_at' => $now,
                ],
            );
            $sort += 10;
        }

        $guest = [
            'places.view', 'reports.create', 'sources.view_public', 'history.view_place',
        ];

        $user = array_merge($guest, [
            'places.suggest', 'places.edit_own_suggestion',
            'reviews.create', 'reviews.edit_own', 'reviews.delete_own', 'reviews.vote_helpful', 'reviews.remove_own_helpful_vote',
            'photos.upload', 'photos.delete_own',
            'users.view', 'users.view_profile',
            'reports.view_own',
            'audit.view_own',
            'owners.request_verification', 'owners.view_own_requests',
            'favorites.manage_own',
            'sources.add', 'sources.edit_own',
            'notifications.view_own', 'notifications.manage_own', 'subscriptions.manage_own',
            'support.create', 'support.view_own', 'support.reply_own',
            'security.view_own_sessions', 'security.revoke_own_sessions', 'security.manage_own_2fa',
            'privacy.export_own_data', 'privacy.request_account_deletion',
        ]);

        $mod = array_merge($user, [
            'places.view_unpublished', 'places.approve_changes', 'places.deactivate', 'places.restore',
            'reviews.view_pending', 'reviews.moderate', 'reviews.delete_any', 'reviews.restore', 'reviews.mark_verified_visit',
            'photos.view_pending', 'photos.moderate', 'photos.delete_any', 'photos.set_place_order', 'photos.set_cover',
            'users.view_details', 'users.warn', 'users.suspend', 'users.unsuspend',
            'reports.view_all', 'reports.handle', 'reports.assign', 'reports.add_internal_note',
            'audit.view_moderation',
            'owner_replies.moderate',
            'sources.view_internal', 'sources.deactivate', 'history.view_internal',
            'notifications.send_moderation',
            'support.view_all', 'support.reply', 'support.internal_note', 'support.change_status', 'support.assign',
        ]);

        $allPermissionSlugs = DB::table('permissions')->where('is_active', true)->pluck('slug')->all();

        foreach ([
            'guest' => $guest,
            'user' => $user,
            'mod' => $mod,
            'admin' => $allPermissionSlugs,
        ] as $roleSlug => $permissionSlugs) {
            $roleId = DB::table('roles')->where('slug', $roleSlug)->value('id');
            if (! $roleId) {
                continue;
            }

            $permissionIds = DB::table('permissions')
                ->whereIn('slug', array_values(array_unique($permissionSlugs)))
                ->pluck('id');

            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['updated_at' => $now, 'created_at' => $now],
                );
            }
        }
    }
}
