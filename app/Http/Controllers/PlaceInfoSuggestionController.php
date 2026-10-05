<?php

namespace App\Http\Controllers;

use App\Services\ChangeRequestApplyService;
use App\Services\CountryService;
use App\Services\PermissionService;
use App\Services\UsageAnalyticsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlaceInfoSuggestionController extends Controller
{
    public function edit(
        Request $request,
        string $slug,
        PermissionService $permissions,
    ): View {
        $canDirectEdit = $permissions->can($request->user(), 'places.edit');
        abort_unless($canDirectEdit || $permissions->can($request->user(), 'places.suggest'), 404);

        $place = $this->place($slug);
        $locale = app()->getLocale();

        $translation = $this->translation((int) $place->id, $locale);
        $details = $this->details((int) $place->id);
        $address = $this->address((int) $place->id);
        $website = $this->contact((int) $place->id, ['website', 'url']);
        $phone = $this->contact((int) $place->id, ['telephone', 'phone']);
        $email = $this->contact((int) $place->id, ['email']);
        $placeTypes = $this->placeTypes($locale);

        $vehicleTypes = DB::table('vehicle_types as vt')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'vt.id')
                    ->where('tr.entity_type', 'vehicle_type')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->where('vt.is_active', true)
            ->orderBy('vt.sort_order')
            ->get([
                'vt.id',
                'vt.slug',
                DB::raw('COALESCE(tr.value, vt.slug) as label'),
            ]);

        $selectedVehicleRows = DB::table('place_vehicle_types')
            ->where('place_id', $place->id)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->get(['vehicle_type_id', 'capacity']);

        $selectedVehicleIds = $selectedVehicleRows
            ->pluck('vehicle_type_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $selectedVehicleCapacities = $selectedVehicleRows
            ->mapWithKeys(fn ($row) => [(int) $row->vehicle_type_id => $row->capacity !== null ? (int) $row->capacity : null])
            ->all();

        return view('places.suggest-info', [
            'place' => $place,
            'translation' => $translation,
            'details' => $details,
            'address' => $address,
            'website' => $website,
            'phone' => $phone,
            'email' => $email,
            'placeTypes' => $placeTypes,
            'vehicleTypes' => $vehicleTypes,
            'selectedVehicleIds' => $selectedVehicleIds,
            'selectedVehicleCapacities' => $selectedVehicleCapacities,
            'countries' => app(CountryService::class)->all($locale),
            'canDirectEdit' => $canDirectEdit,
        ]);
    }

    public function update(
        Request $request,
        string $slug,
        PermissionService $permissions,
        ChangeRequestApplyService $changeRequests,
    ): RedirectResponse {
        $canDirectEdit = $permissions->can($request->user(), 'places.edit');
        abort_unless($canDirectEdit || $permissions->can($request->user(), 'places.suggest'), 404);

        $place = $this->place($slug);
        $locale = app()->getLocale();
        $countryCodes = array_keys(app(CountryService::class)->all('en'));

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'place_type_id' => ['required', 'integer', 'exists:place_types,id'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'legal_status' => ['required', Rule::in(['overnight_allowed', 'camping_allowed', 'parking_only', 'prohibited', 'owner_unwanted', 'unclear'])],
            'operator_name' => ['nullable', 'string', 'max:255'],
            'pitch_count' => ['nullable', 'integer', 'min:0', 'max:1000000'],
            'minimum_stay_nights' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'pitch_area_min_m2' => ['nullable', 'numeric', 'min:0.01', 'max:1000000'],
            'opening_status' => ['required', 'in:open,temporarily_closed,seasonally_closed,permanently_closed,unclear'],
            'website' => ['nullable', 'url:http,https', 'max:1024'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:320'],
            'description' => ['nullable', 'string', 'max:10000'],
            'directions' => ['nullable', 'string', 'max:10000'],
            'access_information' => ['nullable', 'string', 'max:10000'],
            'country_code' => ['nullable', 'string', 'size:2', Rule::in($countryCodes)],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:255'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:32'],
            'address_addition' => ['nullable', 'string', 'max:255'],
            'vehicle_type_ids' => ['nullable', 'array'],
            'vehicle_type_ids.*' => ['integer', 'exists:vehicle_types,id'],
            'vehicle_capacities' => ['nullable', 'array'],
            'vehicle_capacities.*' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $placeTypeIsSelectable = DB::table('place_types')
            ->where('id', $data['place_type_id'])
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->exists();

        if (! $placeTypeIsSelectable) {
            return back()->withInput()->withErrors(['place_type_id' => __('places.suggest.inactive_type')]);
        }

        $translation = $this->translation((int) $place->id, $locale);
        $details = $this->details((int) $place->id);
        $address = $this->address((int) $place->id);
        $website = $this->contact((int) $place->id, ['website', 'url']);
        $phone = $this->contact((int) $place->id, ['telephone', 'phone']);
        $email = $this->contact((int) $place->id, ['email']);

        $changes = [];
        $groupUuid = (string) Str::uuid();

        foreach ([
            'name' => trim($data['name']),
            'place_type_id' => (int) $data['place_type_id'],
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'legal_status' => $data['legal_status'],
        ] as $field => $value) {
            $this->queueScalarUpdate(
                $changes,
                'places',
                $field,
                (int) $place->id,
                $place->{$field},
                $value,
            );
        }

        $this->queueScalarUpdate(
            $changes,
            'places',
            'opening_status',
            (int) $place->id,
            $place->opening_status,
            $data['opening_status'],
        );

        $detailFields = [
            'operator_name' => $data['operator_name'] ?? null,
            'pitch_count' => $data['pitch_count'] ?? null,
            'minimum_stay_nights' => $data['minimum_stay_nights'] ?? null,
            'pitch_area_min_m2' => $data['pitch_area_min_m2'] ?? null,
        ];
        if ($details) {
            foreach ($detailFields as $field => $value) {
                $this->queueScalarUpdate(
                    $changes,
                    'place_details',
                    $field,
                    (int) $details->id,
                    $details->{$field},
                    $value,
                );
            }
        } else {
            foreach ($detailFields as $field => $value) {
                if (filled($value)) {
                    $this->queueCreate($changes, 'place_details', $field, $value);
                }
            }
        }

        $translationFields = [
            'description' => $data['description'] ?? null,
            'directions' => $data['directions'] ?? null,
            'access_information' => $data['access_information'] ?? null,
        ];
        if ($translation) {
            foreach ($translationFields as $field => $value) {
                $this->queueScalarUpdate(
                    $changes,
                    'place_translations',
                    $field,
                    (int) $translation->id,
                    $translation->{$field},
                    $value,
                );
            }
        } else {
            foreach ($translationFields as $field => $value) {
                if (filled($value)) {
                    $changes[] = [
                        'table' => 'place_translations',
                        'field' => $field,
                        'operation' => 'create',
                        'target_record_id' => null,
                        'original' => null,
                        'proposed' => ['locale' => $locale, 'value' => $value],
                    ];
                }
            }
        }

        $addressFields = [
            'country_code' => isset($data['country_code']) ? Str::upper($data['country_code']) : null,
            'postal_code' => $data['postal_code'] ?? null,
            'city' => $data['city'] ?? null,
            'street' => $data['street'] ?? null,
            'house_number' => $data['house_number'] ?? null,
            'address_addition' => $data['address_addition'] ?? null,
        ];
        if ($address) {
            foreach ($addressFields as $field => $value) {
                $this->queueScalarUpdate(
                    $changes,
                    'place_addresses',
                    $field,
                    (int) $address->id,
                    $address->{$field},
                    $value,
                );
            }
        } elseif (collect($addressFields)->contains(fn ($value) => filled($value))) {
            foreach ($addressFields as $field => $value) {
                if (filled($value)) {
                    $this->queueCreate($changes, 'place_addresses', $field, $value);
                }
            }
        }

        $this->queueContactChange(
            $changes,
            $website,
            'website',
            $data['website'] ?? null,
        );
        $this->queueContactChange(
            $changes,
            $phone,
            'telephone',
            $data['phone'] ?? null,
        );
        $this->queueContactChange(
            $changes,
            $email,
            'email',
            $data['email'] ?? null,
        );

        $currentVehicleRows = DB::table('place_vehicle_types')
            ->where('place_id', $place->id)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->get(['id', 'vehicle_type_id', 'capacity'])
            ->keyBy(fn ($row) => (int) $row->vehicle_type_id);

        $requestedVehicleIds = collect($data['vehicle_type_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $requestedVehicleCapacities = collect($data['vehicle_capacities'] ?? [])
            ->mapWithKeys(function ($value, $key) {
                $vehicleTypeId = (int) $key;
                $capacity = is_numeric($value) && (int) $value > 0 ? (int) $value : null;

                return [$vehicleTypeId => $capacity];
            });

        foreach ($requestedVehicleIds as $vehicleTypeId) {
            $requestedCapacity = $requestedVehicleCapacities->get($vehicleTypeId);

            if (! $currentVehicleRows->has($vehicleTypeId)) {
                $changes[] = [
                    'table' => 'place_vehicle_types',
                    'field' => 'vehicle_type_id',
                    'operation' => 'create',
                    'target_record_id' => null,
                    'original' => null,
                    'proposed' => [
                        'vehicle_type_id' => $vehicleTypeId,
                        'capacity' => $requestedCapacity,
                    ],
                ];
                continue;
            }

            $currentRow = $currentVehicleRows->get($vehicleTypeId);
            $currentCapacity = $currentRow->capacity !== null ? (int) $currentRow->capacity : null;

            $this->queueScalarUpdate(
                $changes,
                'place_vehicle_types',
                'capacity',
                (int) $currentRow->id,
                $currentCapacity,
                $requestedCapacity,
            );
        }

        foreach ($currentVehicleRows as $vehicleTypeId => $row) {
            if (! $requestedVehicleIds->contains((int) $vehicleTypeId)) {
                $changes[] = [
                    'table' => 'place_vehicle_types',
                    'field' => 'vehicle_type_id',
                    'operation' => 'deactivate',
                    'target_record_id' => (int) $row->id,
                    'original' => (int) $vehicleTypeId,
                    'proposed' => null,
                ];
            }
        }

        if ($changes === []) {
            return redirect()
                ->route('places.show', $place->slug)
                ->with('ui_toast', __('place_editing.info.status_none'));
        }

        $anchorRequestId = DB::transaction(function () use ($changes, $groupUuid, $place, $request, $data): int {
            $fieldMap = DB::table('suggestable_fields')
                ->where('is_suggestable', true)
                ->where('is_active', true)
                ->get(['id', 'target_table', 'target_field'])
                ->keyBy(fn ($row) => $row->target_table.'.'.$row->target_field);

            $now = now();
            $anchorRequestId = null;

            foreach ($changes as $change) {
                $definition = $fieldMap->get($change['table'].'.'.$change['field']);
                if (! $definition) {
                    throw new \RuntimeException(__('place_editing.info.field_not_enabled', [
                        'field' => $change['table'].'.'.$change['field'],
                    ]));
                }

                $requestId = DB::table('change_requests')->insertGetId([
                    'group_uuid' => $groupUuid,
                    'place_id' => $place->id,
                    'suggestable_field_id' => $definition->id,
                    'target_record_id' => $change['target_record_id'],
                    'operation' => $change['operation'],
                    'original_value' => $this->json($change['original']),
                    'proposed_value' => $this->json($change['proposed']),
                    'status' => 'pending',
                    'submitted_by' => $request->user()->id,
                    'submitted_at' => $now,
                    'user_comment' => $data['comment'] ?? null,
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'moderator_comment' => null,
                    'result_record_id' => null,
                    'applied_at' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $anchorRequestId ??= (int) $requestId;
            }

            return (int) $anchorRequestId;
        });

        app(UsageAnalyticsService::class)->track(
            $request,
            'change_suggestion_submitted',
            'place_information',
            'place',
            (int) $place->id,
            ['direct' => $canDirectEdit],
        );

        if ($canDirectEdit) {
            $changeRequests->approveAndApply(
                $request->user(),
                $anchorRequestId,
                __('place_editing.info.direct_audit_comment'),
            );

            return redirect()
                ->route('places.show', $place->slug)
                ->with('ui_dialog', [
                    'variant' => 'success',
                    'message' => __('place_editing.info.status_saved'),
                ]);
        }

        return redirect()
            ->route('places.show', $place->slug)
            ->with('ui_dialog', [
                'variant' => 'success',
                'message' => __('place_editing.info.status_submitted'),
            ]);
    }

    private function place(string $slug): object
    {
        $place = DB::table('places')
            ->where('slug', $slug)
            ->where('is_active', true)
            ->where('publication_status', 'published')
            ->first([
                'id',
                'name',
                'slug',
                'place_type_id',
                'latitude',
                'longitude',
                'legal_status',
                'opening_status',
            ]);

        abort_unless($place, 404);

        return $place;
    }

    private function placeTypes(string $locale)
    {
        return DB::table('place_types as pt')
            ->leftJoin('translations as tr', function ($join) use ($locale) {
                $join->on('tr.entity_id', '=', 'pt.id')
                    ->where('tr.entity_type', 'place_type')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', $locale)
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as en', function ($join) {
                $join->on('en.entity_id', '=', 'pt.id')
                    ->where('en.entity_type', 'place_type')
                    ->where('en.field', 'name')
                    ->where('en.locale', 'en')
                    ->where('en.is_active', true);
            })
            ->where('pt.is_active', true)
            ->where('pt.is_searchable', true)
            ->orderBy('pt.sort_order')
            ->orderBy('pt.slug')
            ->get([
                'pt.id',
                'pt.slug',
                DB::raw('COALESCE(tr.value, en.value, pt.slug) as label'),
            ]);
    }

    private function translation(int $placeId, string $locale): ?object
    {
        return DB::table('place_translations')
            ->where('place_id', $placeId)
            ->where('locale', $locale)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first(['id', 'description', 'directions', 'access_information']);
    }

    private function details(int $placeId): ?object
    {
        return DB::table('place_details')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first(['id', 'operator_name', 'pitch_count', 'minimum_stay_nights', 'pitch_area_min_m2']);
    }

    private function address(int $placeId): ?object
    {
        return DB::table('place_addresses')
            ->where('place_id', $placeId)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->first([
                'id',
                'country_code',
                'postal_code',
                'city',
                'street',
                'house_number',
                'address_addition',
            ]);
    }

    private function contact(int $placeId, array $types): ?object
    {
        return DB::table('place_contacts')
            ->where('place_id', $placeId)
            ->whereIn('contact_type', $types)
            ->where('is_active', true)
            ->whereNull('version_valid_until')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first(['id', 'contact_type', 'value']);
    }

    private function queueContactChange(
        array &$changes,
        ?object $contact,
        string $contactType,
        mixed $submittedValue,
    ): void {
        $value = trim((string) $submittedValue);
        $value = $value !== '' ? $value : null;

        if ($contact && $value === null) {
            $changes[] = [
                'table' => 'place_contacts',
                'field' => 'value',
                'operation' => 'deactivate',
                'target_record_id' => (int) $contact->id,
                'original' => $contact->value,
                'proposed' => null,
            ];

            return;
        }

        if ($contact) {
            $this->queueScalarUpdate(
                $changes,
                'place_contacts',
                'value',
                (int) $contact->id,
                $contact->value,
                $value,
            );

            return;
        }

        if ($value !== null) {
            $changes[] = [
                'table' => 'place_contacts',
                'field' => 'contact_type',
                'operation' => 'create',
                'target_record_id' => null,
                'original' => null,
                'proposed' => [
                    'contact_type' => $contactType,
                    'value' => $value,
                ],
            ];
        }
    }

    private function queueScalarUpdate(
        array &$changes,
        string $table,
        string $field,
        int $recordId,
        mixed $original,
        mixed $proposed,
    ): void {
        if ($this->equivalent($original, $proposed)) {
            return;
        }

        $changes[] = [
            'table' => $table,
            'field' => $field,
            'operation' => 'update',
            'target_record_id' => $recordId,
            'original' => $original,
            'proposed' => $proposed,
        ];
    }

    private function queueCreate(array &$changes, string $table, string $field, mixed $proposed): void
    {
        $changes[] = [
            'table' => $table,
            'field' => $field,
            'operation' => 'create',
            'target_record_id' => null,
            'original' => null,
            'proposed' => $proposed,
        ];
    }

    private function equivalent(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return (float) $a === (float) $b;
        }

        return trim((string) $a) === trim((string) $b);
    }

    private function json(mixed $value): ?string
    {
        return $value === null
            ? null
            : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
}
