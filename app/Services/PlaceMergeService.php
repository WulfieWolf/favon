<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;

class PlaceMergeService
{
    public const CORE_FIELDS = [
        'name' => ['label' => 'admin.place_merges.fields.name', 'required' => true],
        'place_type_id' => ['label' => 'admin.place_merges.fields.place_type', 'required' => true],
        'latitude' => ['label' => 'admin.place_merges.fields.latitude', 'required' => true],
        'longitude' => ['label' => 'admin.place_merges.fields.longitude', 'required' => true],
        'legal_status' => ['label' => 'admin.place_merges.fields.legal_status', 'required' => false],
    ];

    private const VERSIONED_TABLES = [
        'place_translations' => ['label' => 'admin.place_merges.groups.texts', 'identity' => ['locale']],
        'place_addresses' => ['label' => 'admin.place_merges.groups.address', 'identity' => []],
        'place_details' => ['label' => 'admin.place_merges.groups.details', 'identity' => []],
        'place_features' => ['label' => 'admin.place_merges.groups.features', 'identity' => ['feature_id']],
        'place_contacts' => ['label' => 'admin.place_merges.groups.contacts', 'identity' => ['contact_type', 'value']],
    ];

    public function comparison(int $mainId, int $duplicateId): array
    {
        $main = $this->place($mainId);
        $duplicate = $this->place($duplicateId);
        if ($mainId === $duplicateId) {
            throw new RuntimeException(__('admin.place_merges.errors.same_place'));
        }

        $fields = [];
        foreach (self::CORE_FIELDS as $field => $definition) {
            $definition['label'] = __($definition['label']);
            $left = $main->{$field};
            $right = $duplicate->{$field};
            $fields[$field] = $definition + [
                'main' => $left,
                'duplicate' => $right,
                'suggested' => $this->suggestedChoice($left, $right, $definition['required']),
            ];
        }

        $records = [];
        foreach (self::VERSIONED_TABLES as $table => $definition) {
            $definition['label'] = __($definition['label']);
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($this->compareTable($table, $definition, $mainId, $duplicateId) as $record) {
                $records[] = $record;
            }
        }
        if (Schema::hasTable('place_price_offers')) {
            foreach ($this->comparePriceOffers($mainId, $duplicateId) as $record) {
                $records[] = $record;
            }
        }
        if (Schema::hasTable('opening_hour_periods')) {
            foreach ($this->compareOpeningPeriods($mainId, $duplicateId) as $record) {
                $records[] = $record;
            }
        }

        $pendingChangeRequests = Schema::hasTable('change_requests')
            ? DB::table('change_requests')->whereIn('place_id', [$mainId, $duplicateId])->where('status', 'pending')->count()
            : 0;

        return compact('main', 'duplicate', 'fields', 'records', 'pendingChangeRequests');
    }

    public function resolveActivePlaceId(int $placeId): ?int
    {
        $currentId = $placeId;
        $visited = [];

        for ($depth = 0; $depth < 50; $depth++) {
            if (isset($visited[$currentId])) {
                return null;
            }
            $visited[$currentId] = true;

            $place = DB::table('places')
                ->where('id', $currentId)
                ->first(['id', 'is_active', 'deleted_at']);

            if (! $place) {
                return null;
            }

            if ((bool) $place->is_active && ! $place->deleted_at) {
                return (int) $place->id;
            }

            $targetId = DB::table('place_merges')
                ->where('source_place_id', $currentId)
                ->where('status', 'completed')
                ->orderByDesc('id')
                ->value('target_place_id');

            if (! $targetId) {
                return null;
            }

            $currentId = (int) $targetId;
        }

        return null;
    }

    public function merge(User $actor, int $mainId, int $duplicateId, array $fieldChoices, array $recordChoices): int
    {
        return DB::transaction(function () use ($actor, $mainId, $duplicateId, $fieldChoices, $recordChoices): int {
            $comparison = $this->comparison($mainId, $duplicateId);
            $main = DB::table('places')->where('id', $mainId)->lockForUpdate()->first();
            $duplicate = DB::table('places')->where('id', $duplicateId)->lockForUpdate()->first();

            if (! $main || ! $duplicate || ! $main->is_active || ! $duplicate->is_active) {
                throw new RuntimeException(__('admin.place_merges.errors.inactive_place'));
            }
            if (DB::table('place_merges')->where('source_place_id', $duplicateId)->where('status', 'completed')->exists()) {
                throw new RuntimeException(__('admin.place_merges.errors.duplicate_already_merged'));
            }
            if ($comparison['pendingChangeRequests'] > 0) {
                throw new RuntimeException(__('admin.place_merges.errors.pending_changes'));
            }

            $snapshot = $this->snapshot($mainId, $duplicateId);
            $decisions = ['fields' => $fieldChoices, 'records' => $recordChoices];
            $mergeId = (int) DB::table('place_merges')->insertGetId([
                'source_place_id' => $duplicateId,
                'target_place_id' => $mainId,
                'merged_by' => $actor->id,
                'status' => 'completed',
                'decisions' => json_encode($decisions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'snapshot' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'merged_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $updates = [];
            foreach ($comparison['fields'] as $field => $definition) {
                $choice = $fieldChoices[$field] ?? $definition['suggested'];
                if ($choice === 'duplicate') {
                    $updates[$field] = $definition['duplicate'];
                } elseif ($choice === 'delete' && ! $definition['required']) {
                    $updates[$field] = $this->emptyValueFor($definition['main']);
                } elseif ($choice !== 'main') {
                    throw new RuntimeException(__('admin.place_merges.errors.invalid_field_choice', ['field' => $definition['label']]));
                }
            }
            $updates['updated_at'] = now();
            DB::table('places')->where('id', $mainId)->update($updates);

            foreach ($comparison['records'] as $record) {
                $choice = $recordChoices[$record['token']] ?? $record['suggested'];
                $this->applyRecordChoice($record, $choice, $mainId);
            }

            $this->mergeReviews($mainId, $duplicateId);
            $this->mergeFavorites($mainId, $duplicateId);
            $this->mergeDataSources($mainId, $duplicateId);
            $this->mergeExternalRecords($mainId, $duplicateId);

            DB::table('places')->where('id', $duplicateId)->update([
                'is_active' => false,
                'internal_comment' => 'Zusammengeführt mit Platz #'.$mainId.'.',
                'updated_at' => now(),
            ]);

            DB::table('audit_logs')->insert([
                'user_id' => $actor->id,
                'entity_type' => 'place_merge',
                'entity_id' => $mergeId,
                'action' => 'places_merged',
                'source' => 'admin',
                'old_values' => json_encode(['source_place_id' => $duplicateId], JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode(['target_place_id' => $mainId, 'decisions' => $decisions], JSON_UNESCAPED_UNICODE),
                'internal_comment' => null,
                'created_at' => now(),
            ]);

            return $mergeId;
        }, 3);
    }

    public function reverse(User $actor, int $mergeId): void
    {
        DB::transaction(function () use ($actor, $mergeId): void {
            $merge = DB::table('place_merges')->where('id', $mergeId)->lockForUpdate()->first();
            if (! $merge || $merge->status !== 'completed') {
                throw new RuntimeException(__('admin.place_merges.errors.merge_inactive'));
            }
            $targetUpdatedAt = DB::table('places')->where('id', $merge->target_place_id)->value('updated_at');
            if ($targetUpdatedAt && Carbon::parse($targetUpdatedAt)->gt(Carbon::parse($merge->merged_at)->addSecond())) {
                throw new RuntimeException(__('admin.place_merges.errors.main_changed'));
            }

            $snapshot = json_decode($merge->snapshot, true, 512, JSON_THROW_ON_ERROR);
            foreach ($snapshot['places'] ?? [] as $row) {
                $id = $row['id'];
                unset($row['id']);
                DB::table('places')->where('id', $id)->update($row);
            }

            foreach (array_keys(self::VERSIONED_TABLES) as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $rows = collect($snapshot[$table] ?? []);
                $snapshotIds = $rows->pluck('id')->map(fn ($id) => (int) $id)->all();
                $current = $this->currentRows($table, (int) $merge->target_place_id);
                foreach ($current as $row) {
                    if (! in_array((int) $row->id, $snapshotIds, true)) {
                        $this->retireRow($table, (int) $row->id);
                    }
                }
                foreach ($rows as $row) {
                    $id = $row['id'];
                    unset($row['id']);
                    DB::table($table)->where('id', $id)->update($row);
                }
            }

            if (Schema::hasTable('place_price_offers')) {
                $offerRows = collect($snapshot['place_price_offers'] ?? []);
                $snapshotOfferIds = $offerRows->pluck('id')->map(fn ($id) => (int) $id)->all();
                foreach ($this->currentRows('place_price_offers', (int) $merge->target_place_id) as $offer) {
                    if (! in_array((int) $offer->id, $snapshotOfferIds, true)) {
                        $this->retirePriceOffer((int) $offer->id);
                    }
                }
                foreach ($offerRows as $row) {
                    $id = $row['id'];
                    unset($row['id']);
                    DB::table('place_price_offers')->where('id', $id)->update($row);
                }
                foreach (['place_price_periods', 'place_price_lines'] as $childTable) {
                    foreach ($snapshot[$childTable] ?? [] as $row) {
                        $id = $row['id'];
                        unset($row['id']);
                        DB::table($childTable)->where('id', $id)->update($row);
                    }
                }
            }

            if (Schema::hasTable('opening_hour_periods')) {
                $periodRows = collect($snapshot['opening_hour_periods'] ?? []);
                $snapshotPeriodIds = $periodRows->pluck('id')->map(fn ($id) => (int) $id)->all();
                foreach ($this->currentRows('opening_hour_periods', (int) $merge->target_place_id) as $period) {
                    if (! in_array((int) $period->id, $snapshotPeriodIds, true)) {
                        $this->retireOpeningPeriod((int) $period->id);
                    }
                }
                foreach ($periodRows as $row) {
                    $id = $row['id'];
                    unset($row['id']);
                    DB::table('opening_hour_periods')->where('id', $id)->update($row);
                }
                foreach ($snapshot['opening_hours'] ?? [] as $row) {
                    $id = $row['id'];
                    unset($row['id']);
                    DB::table('opening_hours')->where('id', $id)->update($row);
                }
            }

            foreach ($snapshot['place_reviews'] ?? [] as $row) {
                $id = $row['id'];
                unset($row['id']);
                $currentVersionId = $row['current_version_id'] ?? null;
                DB::table('place_reviews')->where('id', $id)->update($row);
                if ($currentVersionId) {
                    DB::table('place_review_versions')->where('review_id', $id)->where('id', '!=', $currentVersionId)
                        ->where('created_at', '>=', $merge->merged_at)->update(['is_public' => false, 'updated_at' => now()]);
                }
            }
            foreach (['place_favorites', 'place_data_sources'] as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                DB::table($table)->whereIn('place_id', [$merge->source_place_id, $merge->target_place_id])->delete();
                foreach ($snapshot[$table] ?? [] as $row) {
                    DB::table($table)->insert($row);
                }
            }

            if (Schema::hasTable('external_records')) {
                DB::table('external_records')
                    ->whereIn('place_id', [$merge->source_place_id, $merge->target_place_id])
                    ->update(['place_id' => null, 'updated_at' => now()]);

                foreach ($snapshot['external_records'] ?? [] as $row) {
                    $id = (int) $row['id'];
                    DB::table('external_records')->where('id', $id)->update([
                        'place_id' => $row['place_id'] ?? null,
                        'classification' => $row['classification'] ?? null,
                        'classified_at' => $row['classified_at'] ?? null,
                        'updated_at' => now(),
                    ]);
                }
            }

            DB::table('place_merges')->where('id', $mergeId)->update([
                'status' => 'reversed', 'reversed_by' => $actor->id, 'reversed_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('audit_logs')->insert([
                'user_id' => $actor->id, 'entity_type' => 'place_merge', 'entity_id' => $mergeId,
                'action' => 'place_merge_reversed', 'source' => 'admin', 'old_values' => null,
                'new_values' => json_encode(['restored_source_place_id' => $merge->source_place_id], JSON_UNESCAPED_UNICODE),
                'internal_comment' => null, 'created_at' => now(),
            ]);
        }, 3);
    }

    private function place(int $id): object
    {
        $place = DB::table('places')->where('id', $id)->first();
        if (! $place) {
            throw new RuntimeException(__('admin.place_merges.errors.place_missing', ['id' => $id]));
        }

        return $place;
    }

    private function suggestedChoice(mixed $main, mixed $duplicate, bool $required = false): string
    {
        if ($this->equivalent($main, $duplicate)) {
            return 'main';
        }
        if ($this->blank($main) && ! $this->blank($duplicate)) {
            return 'duplicate';
        }
        if (! $this->blank($main) && $this->blank($duplicate)) {
            return 'main';
        }

        return $required ? 'main' : 'delete';
    }

    private function compareTable(string $table, array $definition, int $mainId, int $duplicateId): array
    {
        $mainRows = $this->currentRows($table, $mainId)->keyBy(fn ($row) => $this->rowIdentity($row, $definition['identity']));
        $duplicateRows = $this->currentRows($table, $duplicateId)->keyBy(fn ($row) => $this->rowIdentity($row, $definition['identity']));
        $keys = $mainRows->keys()->merge($duplicateRows->keys())->unique()->sort()->values();
        $result = [];

        foreach ($keys as $identity) {
            $left = $mainRows->get($identity);
            $right = $duplicateRows->get($identity);
            $leftPayload = $left ? $this->businessPayload($table, $left) : null;
            $rightPayload = $right ? $this->businessPayload($table, $right) : null;
            $token = hash('sha256', $table.'|'.$identity);
            $result[] = [
                'token' => $token,
                'table' => $table,
                'group' => $definition['label'],
                'identity' => $identity === '__single__' ? __('admin.place_merges.generic_record') : $identity,
                'main_row_id' => $left->id ?? null,
                'duplicate_row_id' => $right->id ?? null,
                'main' => $leftPayload,
                'duplicate' => $rightPayload,
                'suggested' => $this->suggestedChoice($leftPayload, $rightPayload),
            ];
        }

        return $result;
    }

    private function currentRows(string $table, int $placeId)
    {
        $query = DB::table($table)->where('place_id', $placeId);
        $columns = Schema::getColumnListing($table);
        if (in_array('is_active', $columns, true)) {
            $query->where('is_active', true);
        }
        if (in_array('version_valid_until', $columns, true)) {
            $query->whereNull('version_valid_until');
        }

        return $query->get();
    }

    private function rowIdentity(object $row, array $columns): string
    {
        if ($columns === []) {
            return '__single__';
        }

        return implode(' · ', array_map(fn ($column) => (string) ($row->{$column} ?? '—'), $columns));
    }

    private function businessPayload(string $table, object $row): array
    {
        $ignored = ['id', 'place_id', 'is_active', 'version_valid_from', 'version_valid_until', 'created_at', 'updated_at', 'created_by', 'internal_comment', '_periods', '_lines'];

        return collect((array) $row)->except($ignored)->all();
    }

    private function applyRecordChoice(array $record, string $choice, int $mainId): void
    {
        if (! in_array($choice, ['main', 'duplicate', 'delete'], true)) {
            throw new RuntimeException(__('admin.place_merges.errors.invalid_record_choice'));
        }
        $table = $record['table'];
        if ($table === 'place_price_offers') {
            $this->applyPriceOfferChoice($record, $choice, $mainId);

            return;
        }
        if ($table === 'opening_hour_periods') {
            $this->applyOpeningPeriodChoice($record, $choice, $mainId);

            return;
        }
        if ($choice === 'main') {
            return;
        }
        if ($record['main_row_id']) {
            $this->retireRow($table, (int) $record['main_row_id']);
        }
        if ($choice !== 'duplicate' || ! $record['duplicate_row_id']) {
            return;
        }

        $source = (array) DB::table($table)->where('id', $record['duplicate_row_id'])->first();
        unset($source['id']);
        $source['place_id'] = $mainId;
        if (array_key_exists('version_valid_from', $source)) {
            $source['version_valid_from'] = now();
            $source['version_valid_until'] = null;
        }
        if (array_key_exists('is_active', $source)) {
            $source['is_active'] = true;
        }
        if (array_key_exists('created_at', $source)) {
            $source['created_at'] = now();
        }
        if (array_key_exists('updated_at', $source)) {
            $source['updated_at'] = now();
        }
        DB::table($table)->insert($source);
    }

    private function retireRow(string $table, int $id): void
    {
        $columns = Schema::getColumnListing($table);
        $values = [];
        if (in_array('is_active', $columns, true)) {
            $values['is_active'] = false;
        }
        if (in_array('version_valid_until', $columns, true)) {
            $values['version_valid_until'] = now();
        }
        if (in_array('updated_at', $columns, true)) {
            $values['updated_at'] = now();
        }
        if ($values !== []) {
            DB::table($table)->where('id', $id)->update($values);
        }
    }

    private function mergeReviews(int $mainId, int $duplicateId): void
    {
        $targetByUser = DB::table('place_reviews')->where('place_id', $mainId)->get()->keyBy('user_id');
        $sourceReviews = DB::table('place_reviews')->where('place_id', $duplicateId)->orderBy('id')->get();

        foreach ($sourceReviews as $source) {
            $target = $targetByUser->get($source->user_id);

            if (! $target) {
                DB::table('place_reviews')->where('id', $source->id)->update([
                    'place_id' => $mainId,
                    'updated_at' => now(),
                ]);
                $targetByUser->put($source->user_id, $source);
                continue;
            }

            $sourceIsNewer = ($source->current_published_at ?? '') > ($target->current_published_at ?? '');
            if ($sourceIsNewer && $source->current_version_id) {
                $version = (array) DB::table('place_review_versions')->where('id', $source->current_version_id)->first();
                unset($version['id']);
                $version['review_id'] = $target->id;
                $version['version_number'] = ((int) DB::table('place_review_versions')->where('review_id', $target->id)->max('version_number')) + 1;
                $version['created_at'] = now();
                $version['updated_at'] = now();

                $newVersionId = (int) DB::table('place_review_versions')->insertGetId($version);
                DB::table('place_reviews')->where('id', $target->id)->update([
                    'current_version_id' => $newVersionId,
                    'status' => 'active',
                    'current_published_at' => $source->current_published_at,
                    'current_expires_at' => $source->current_expires_at,
                    'updated_at' => now(),
                ]);
            }

            DB::table('place_reviews')->where('id', $source->id)->update([
                'status' => 'merged_archived',
                'updated_at' => now(),
            ]);
        }
    }

    private function comparePriceOffers(int $mainId, int $duplicateId): array
    {
        $load = function (int $placeId) {
            return $this->currentRows('place_price_offers', $placeId)->map(function ($offer) {
                $periods = DB::table('place_price_periods')->where('place_price_offer_id', $offer->id)
                    ->when(Schema::hasColumn('place_price_periods', 'is_active'), fn ($q) => $q->where('is_active', true))
                    ->when(Schema::hasColumn('place_price_periods', 'version_valid_until'), fn ($q) => $q->whereNull('version_valid_until'))
                    ->get();
                $periodIds = $periods->pluck('id');
                $lines = $periodIds->isEmpty() ? collect() : DB::table('place_price_lines')->whereIn('place_price_period_id', $periodIds)
                    ->when(Schema::hasColumn('place_price_lines', 'is_active'), fn ($q) => $q->where('is_active', true))
                    ->when(Schema::hasColumn('place_price_lines', 'version_valid_until'), fn ($q) => $q->whereNull('version_valid_until'))
                    ->get();
                $offer->_periods = $periods;
                $offer->_lines = $lines;

                return $offer;
            })->keyBy(fn ($offer) => implode(':', [
                $offer->price_product_id ?? 'none', $offer->price_product_variant_id ?? 'none', $offer->feature_id ?? 'none',
            ]));
        };

        $main = $load($mainId);
        $duplicate = $load($duplicateId);
        $result = [];
        foreach ($main->keys()->merge($duplicate->keys())->unique()->sort() as $identity) {
            $left = $main->get($identity);
            $right = $duplicate->get($identity);
            $payload = function ($offer): ?array {
                if (! $offer) {
                    return null;
                }

                return [
                    'offer' => $this->businessPayload('place_price_offers', $offer),
                    'periods' => $offer->_periods->map(fn ($r) => $this->businessPayload('place_price_periods', $r))->all(),
                    'lines' => $offer->_lines->map(fn ($r) => $this->businessPayload('place_price_lines', $r))->all(),
                ];
            };
            $leftPayload = $payload($left);
            $rightPayload = $payload($right);
            $result[] = [
                'token' => hash('sha256', 'place_price_offers|'.$identity),
                'table' => 'place_price_offers', 'group' => __('admin.place_merges.groups.structured_prices'), 'identity' => $identity,
                'main_row_id' => $left->id ?? null, 'duplicate_row_id' => $right->id ?? null,
                'main' => $leftPayload, 'duplicate' => $rightPayload,
                'suggested' => $this->suggestedChoice($leftPayload, $rightPayload),
            ];
        }

        return $result;
    }

    private function applyPriceOfferChoice(array $record, string $choice, int $mainId): void
    {
        if ($record['main_row_id'] && $choice !== 'main') {
            $this->retirePriceOffer((int) $record['main_row_id']);
        }
        if ($choice !== 'duplicate' || ! $record['duplicate_row_id']) {
            return;
        }
        $sourceOffer = (array) DB::table('place_price_offers')->where('id', $record['duplicate_row_id'])->first();
        $sourceOfferId = (int) $sourceOffer['id'];
        unset($sourceOffer['id']);
        $sourceOffer['place_id'] = $mainId;
        $this->prepareClone($sourceOffer);
        $newOfferId = (int) DB::table('place_price_offers')->insertGetId($sourceOffer);
        $periods = DB::table('place_price_periods')->where('place_price_offer_id', $sourceOfferId)->get();
        foreach ($periods as $period) {
            $periodValues = (array) $period;
            $oldPeriodId = (int) $periodValues['id'];
            unset($periodValues['id']);
            $periodValues['place_price_offer_id'] = $newOfferId;
            $this->prepareClone($periodValues);
            $newPeriodId = (int) DB::table('place_price_periods')->insertGetId($periodValues);
            foreach (DB::table('place_price_lines')->where('place_price_period_id', $oldPeriodId)->get() as $line) {
                $lineValues = (array) $line;
                unset($lineValues['id']);
                $lineValues['place_price_period_id'] = $newPeriodId;
                $this->prepareClone($lineValues);
                DB::table('place_price_lines')->insert($lineValues);
            }
        }
    }

    private function compareOpeningPeriods(int $mainId, int $duplicateId): array
    {
        $load = function (int $placeId) {
            return $this->currentRows('opening_hour_periods', $placeId)->map(function ($period) {
                $period->_hours = DB::table('opening_hours')->where('period_id', $period->id)
                    ->when(Schema::hasColumn('opening_hours', 'is_active'), fn ($q) => $q->where('is_active', true))
                    ->when(Schema::hasColumn('opening_hours', 'version_valid_until'), fn ($q) => $q->whereNull('version_valid_until'))
                    ->get();

                return $period;
            })->keyBy(fn ($period) => implode(':', [
                $period->is_year_round ?? 0, $period->start_month ?? 0, $period->start_day ?? 0,
                $period->end_month ?? 0, $period->end_day ?? 0,
            ]));
        };
        $main = $load($mainId);
        $duplicate = $load($duplicateId);
        $result = [];
        foreach ($main->keys()->merge($duplicate->keys())->unique()->sort() as $identity) {
            $left = $main->get($identity);
            $right = $duplicate->get($identity);
            $payload = function ($period): ?array {
                if (! $period) {
                    return null;
                }

                return [
                    'period' => collect($this->businessPayload('opening_hour_periods', $period))->except('_hours')->all(),
                    'hours' => $period->_hours->map(fn ($r) => $this->businessPayload('opening_hours', $r))->all(),
                ];
            };
            $leftPayload = $payload($left);
            $rightPayload = $payload($right);
            $result[] = [
                'token' => hash('sha256', 'opening_hour_periods|'.$identity),
                'table' => 'opening_hour_periods', 'group' => __('admin.place_merges.groups.opening_periods'), 'identity' => $identity,
                'main_row_id' => $left->id ?? null, 'duplicate_row_id' => $right->id ?? null,
                'main' => $leftPayload, 'duplicate' => $rightPayload,
                'suggested' => $this->suggestedChoice($leftPayload, $rightPayload),
            ];
        }

        return $result;
    }

    private function applyOpeningPeriodChoice(array $record, string $choice, int $mainId): void
    {
        if ($record['main_row_id'] && $choice !== 'main') {
            $this->retireOpeningPeriod((int) $record['main_row_id']);
        }
        if ($choice !== 'duplicate' || ! $record['duplicate_row_id']) {
            return;
        }
        $source = (array) DB::table('opening_hour_periods')->where('id', $record['duplicate_row_id'])->first();
        $sourceId = (int) $source['id'];
        unset($source['id']);
        $source['place_id'] = $mainId;
        $this->prepareClone($source);
        if (isset($source['period_uuid'])) {
            $source['period_uuid'] = (string) Str::uuid();
        }
        $newPeriodId = (int) DB::table('opening_hour_periods')->insertGetId($source);
        foreach (DB::table('opening_hours')->where('period_id', $sourceId)->get() as $hour) {
            $values = (array) $hour;
            unset($values['id']);
            $values['period_id'] = $newPeriodId;
            $this->prepareClone($values);
            DB::table('opening_hours')->insert($values);
        }
    }

    private function retireOpeningPeriod(int $periodId): void
    {
        foreach (DB::table('opening_hours')->where('period_id', $periodId)->pluck('id') as $hourId) {
            $this->retireRow('opening_hours', (int) $hourId);
        }
        $this->retireRow('opening_hour_periods', $periodId);
    }

    private function retirePriceOffer(int $offerId): void
    {
        $periodIds = DB::table('place_price_periods')->where('place_price_offer_id', $offerId)->pluck('id');
        foreach ($periodIds as $periodId) {
            foreach (DB::table('place_price_lines')->where('place_price_period_id', $periodId)->pluck('id') as $lineId) {
                $this->retireRow('place_price_lines', (int) $lineId);
            }
            $this->retireRow('place_price_periods', (int) $periodId);
        }
        $this->retireRow('place_price_offers', $offerId);
    }

    private function prepareClone(array &$values): void
    {
        if (array_key_exists('is_active', $values)) {
            $values['is_active'] = true;
        }
        if (array_key_exists('version_valid_from', $values)) {
            $values['version_valid_from'] = now();
        }
        if (array_key_exists('version_valid_until', $values)) {
            $values['version_valid_until'] = null;
        }
        if (array_key_exists('created_at', $values)) {
            $values['created_at'] = now();
        }
        if (array_key_exists('updated_at', $values)) {
            $values['updated_at'] = now();
        }
    }

    private function createPhotoConflicts(int $mergeId, int $placeId, User $actor): void
    {
        $limit = (int) config('photos.max_per_review', 5);
        $users = DB::table('place_photos as pp')
            ->join('photos as p', 'p.id', '=', 'pp.photo_id')
            ->where('pp.place_id', $placeId)
            ->where('pp.is_active', true)
            ->where('p.is_active', true)
            ->whereIn('p.status', ['processing', 'pending', 'approved'])
            ->groupBy('p.user_id')
            ->havingRaw('COUNT(*) > ?', [$limit])
            ->get(['p.user_id', DB::raw('COUNT(*) as total')]);

        foreach ($users as $row) {
            $userId = (int) $row->user_id;
            $total = (int) $row->total;
            $conflictId = (int) DB::table('photo_merge_conflicts')->insertGetId([
                'place_merge_id' => $mergeId,
                'place_id' => $placeId,
                'user_id' => $userId,
                'status' => 'selection_required',
                'photo_limit' => $limit,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $photoIds = DB::table('place_photos as pp')
                ->join('photos as p', 'p.id', '=', 'pp.photo_id')
                ->where('pp.place_id', $placeId)->where('pp.is_active', true)
                ->where('p.user_id', $userId)->where('p.is_active', true)
                ->whereIn('p.status', ['processing', 'pending', 'approved'])->pluck('p.id');
            foreach ($photoIds as $photoId) {
                DB::table('photo_merge_conflict_items')->insert([
                    'photo_merge_conflict_id' => $conflictId,
                    'photo_id' => $photoId,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $notifications = app(UserNotificationService::class);
            $locale = $notifications->userLocale($userId);
            $notifications->createImmediate(
                (int) $userId,
                'photo_merge_selection_required',
                __('notifications.photo_merge_title', [], $locale),
                __('notifications.photo_merge_message', ['total' => $total, 'limit' => $limit], $locale),
                route('my.photos.index', ['conflict' => $conflictId]),
                'important', 'photo', ['conflict_id' => $conflictId, 'place_id' => $placeId], (int) $actor->id,
            );
        }
    }

    private function mergeFavorites(int $mainId, int $duplicateId): void
    {
        $rows = DB::table('place_favorites')->where('place_id', $duplicateId)->get();
        foreach ($rows as $row) {
            $values = (array) $row;
            unset($values['id']);
            $values['place_id'] = $mainId;
            DB::table('place_favorites')->insertOrIgnore($values);
        }
        DB::table('place_favorites')->where('place_id', $duplicateId)->delete();
    }

    private function mergeExternalRecords(int $mainId, int $duplicateId): void
    {
        if (! Schema::hasTable('external_records')) {
            return;
        }

        DB::table('external_records')
            ->where('place_id', $duplicateId)
            ->update([
                'place_id' => $mainId,
                'updated_at' => now(),
            ]);
    }

    private function mergePhotoSettings(int $mainId, int $duplicateId): void
    {
        $main = DB::table('place_photo_settings')->where('place_id', $mainId)->first();
        $source = DB::table('place_photo_settings')->where('place_id', $duplicateId)->first();
        if ($source) {
            DB::table('place_photo_settings')->updateOrInsert(['place_id' => $mainId], [
                'fallback_photo_id' => $main?->fallback_photo_id ?: $source->fallback_photo_id,
                'vote_photo_id' => null,
                'admin_photo_id' => $main?->admin_photo_id ?: $source->admin_photo_id,
                'admin_selected_by' => $main?->admin_selected_by ?: $source->admin_selected_by,
                'admin_selected_at' => $main?->admin_selected_at ?: $source->admin_selected_at,
                'created_at' => $main?->created_at ?: now(),
                'updated_at' => now(),
            ]);
            DB::table('place_photo_settings')->where('place_id', $duplicateId)->delete();
        }
    }

    private function mergeDataSources(int $mainId, int $duplicateId): void
    {
        if (! Schema::hasTable('place_data_sources')) {
            return;
        }
        foreach (DB::table('place_data_sources')->where('place_id', $duplicateId)->get() as $row) {
            $values = (array) $row;
            unset($values['id']);
            $values['place_id'] = $mainId;
            DB::table('place_data_sources')->insertOrIgnore($values);
        }
        DB::table('place_data_sources')->where('place_id', $duplicateId)->delete();
    }

    private function snapshot(int $mainId, int $duplicateId): array
    {
        $snapshot = ['places' => DB::table('places')->whereIn('id', [$mainId, $duplicateId])->get()->map(fn ($r) => (array) $r)->all()];
        foreach (array_keys(self::VERSIONED_TABLES) as $table) {
            if (Schema::hasTable($table)) {
                $snapshot[$table] = DB::table($table)->whereIn('place_id', [$mainId, $duplicateId])->get()->map(fn ($r) => (array) $r)->all();
            }
        }
        if (Schema::hasTable('place_price_offers')) {
            $offers = DB::table('place_price_offers')->whereIn('place_id', [$mainId, $duplicateId])->get();
            $snapshot['place_price_offers'] = $offers->map(fn ($r) => (array) $r)->all();
            $periods = DB::table('place_price_periods')->whereIn('place_price_offer_id', $offers->pluck('id'))->get();
            $snapshot['place_price_periods'] = $periods->map(fn ($r) => (array) $r)->all();
            $snapshot['place_price_lines'] = DB::table('place_price_lines')->whereIn('place_price_period_id', $periods->pluck('id'))->get()->map(fn ($r) => (array) $r)->all();
        }
        if (Schema::hasTable('opening_hour_periods')) {
            $periods = DB::table('opening_hour_periods')->whereIn('place_id', [$mainId, $duplicateId])->get();
            $snapshot['opening_hour_periods'] = $periods->map(fn ($r) => (array) $r)->all();
            $snapshot['opening_hours'] = DB::table('opening_hours')->whereIn('period_id', $periods->pluck('id'))->get()->map(fn ($r) => (array) $r)->all();
        }
        if (Schema::hasTable('external_records')) {
            $snapshot['external_records'] = DB::table('external_records')
                ->whereIn('place_id', [$mainId, $duplicateId])
                ->get()
                ->map(fn ($r) => (array) $r)
                ->all();
        }

        foreach (['place_reviews', 'place_favorites', 'place_data_sources'] as $table) {
            if (Schema::hasTable($table)) {
                $snapshot[$table] = DB::table($table)->whereIn('place_id', [$mainId, $duplicateId])->get()->map(fn ($r) => (array) $r)->all();
            }
        }

        return $snapshot;
    }

    private function equivalent(mixed $a, mixed $b): bool
    {
        return json_encode($a) === json_encode($b);
    }

    private function blank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [] || $value === 'unclear';
    }

    private function emptyValueFor(mixed $value): mixed
    {
        return is_string($value) && $value === 'unclear' ? 'unclear' : null;
    }
}
