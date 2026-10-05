<?php

namespace App\Services;

use App\Models\User;
use App\Support\LocaleConfiguration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PlaceHistoryPresenter
{
    public function forPlace(int $placeId): Collection
    {
        return DB::table('place_history as ph')
            ->leftJoin('users as u', 'u.id', '=', 'ph.user_id')
            ->leftJoin('user_profiles as up', 'up.user_id', '=', 'u.id')
            ->leftJoin('external_sources as es', 'es.id', '=', 'ph.external_source_id')
            ->where('ph.place_id', $placeId)
            ->where('ph.is_public', true)
            ->orderByDesc('ph.created_at')
            ->orderByDesc('ph.id')
            ->get([
                'ph.id', 'ph.actor_type', 'ph.action', 'ph.summary', 'ph.metadata', 'ph.created_at',
                'u.name as user_name', 'u.account_status', 'up.public_alias', 'up.public_handle',
                'es.name as source_name',
            ])
            ->map(function ($row) {
                $metadata = json_decode((string) ($row->metadata ?? ''), true) ?: [];
                $metadataAuthor = trim((string) ($metadata['author'] ?? ''));
                $row->actor = $metadataAuthor !== ''
                    ? $metadataAuthor
                    : match ($row->actor_type) {
                        'external_source' => __('place_profile.history.external_source'),
                        'user' => (($row->account_status ?? 'active') === 'active')
                            ? ($row->public_alias ?: $row->public_handle ?: $row->user_name ?: __('place_profile.history.user'))
                            : ($row->user_name ?: __('place_profile.history.user')),
                        default => __('place_profile.history.system'),
                    };
                $row->actor_profile_handle = $metadataAuthor === ''
                    && $row->actor_type === 'user'
                    && (($row->account_status ?? 'active') === 'active')
                    && $row->public_handle
                        ? $row->public_handle
                        : null;

                $metadataSourceLabel = trim((string) ($metadata['source_label'] ?? ''));
                $metadataSourceUrl = trim((string) ($metadata['source_url'] ?? ''));
                $row->source_label = $metadataSourceLabel !== ''
                    ? $metadataSourceLabel
                    : ($row->actor_type === 'external_source' ? $row->source_name : null);
                $row->source_url = $this->safeHttpUrl($metadataSourceUrl);

                $actionKey = 'place_profile.history.actions.'.(string) $row->action;
                if (\Illuminate\Support\Facades\Lang::has($actionKey)) {
                    $row->summary = __($actionKey);
                }

                $row->submitted_at = $metadata['submitted_at'] ?? $metadata['researched_at'] ?? $row->created_at;
                $row->approved_at = $metadata['approved_at'] ?? null;

                $researchChanges = collect($metadata['research_changes'] ?? [])
                    ->filter(fn ($change) => is_array($change) && is_string($change['field'] ?? null))
                    ->map(fn ($change) => $this->researchChange(
                        (string) $change['field'],
                        $change['old'] ?? null,
                        $change['new'] ?? null,
                    ))
                    ->filter();

                $row->changes = $researchChanges->isNotEmpty()
                    ? $researchChanges->values()
                    : collect($metadata['changes'] ?? [])->filter(fn ($v) => is_string($v) && trim($v) !== '')->values();

                return $row;
            });
    }

    public function changesFromRequests(Collection $requests): array
    {
        return $requests
            ->reject(fn ($r) => $r->target_table === 'places' && $r->target_field === 'publication_status')
            ->map(function ($r) {
                $old = $this->decode($r->original_value);
                $new = $this->decode($r->proposed_value);
                $label = $this->fieldLabel((string) $r->target_table, (string) $r->target_field, $old, $new);

                if ($r->target_table === 'place_features') {
                    return null; // rendered as one compact feature state below
                }

                return $label.': '.$this->value($r->target_table, $r->target_field, $old).' → '.$this->value($r->target_table, $r->target_field, $new);
            })
            ->filter()
            ->merge($this->featureChanges($requests))
            ->values()
            ->all();
    }

    private function researchChange(string $field, mixed $old, mixed $new): string
    {
        $labels = __('place_profile.history.research_fields');
        $label = is_array($labels) ? ($labels[$field] ?? null) : null;

        if (! $label && str_starts_with($field, 'suitable_')) {
            $vehicle = Str::headline(str_replace(['-', '_'], ' ', Str::after($field, 'suitable_')));
            $label = __('place_profile.history.suitable_prefix', ['vehicle' => $vehicle]);
        }

        $label ??= Str::headline(str_replace('_', ' ', $field));

        return $label.': '.$this->researchValue($field, $old).' → '.$this->researchValue($field, $new);
    }

    private function researchValue(string $field, mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('place_profile.unknown');
        }

        if ($field === 'opening_status') {
            return match ((string) $value) {
                'unclear' => __('place_profile.unknown'),
                'open' => __('ui.browse.operating_status.active'),
                'temporarily_closed' => __('ui.browse.operating_status.temporarily_closed'),
                'seasonally_closed' => __('ui.browse.operating_status.seasonally_closed'),
                'permanently_closed' => __('ui.browse.operating_status.permanently_closed'),
                default => Str::headline((string) $value),
            };
        }

        if ($field === 'legal_status') {
            $labels = __('place_editing.info.legal_status_options');

            return is_array($labels)
                ? ($labels[(string) $value] ?? Str::headline((string) $value))
                : Str::headline((string) $value);
        }

        if (str_starts_with($field, 'suitable_')) {
            if ($value === true || $value === 'yes') {
                return __('place_profile.history.yes');
            }

            if ($value === false || $value === 'no') {
                return __('place_profile.history.no');
            }
        }

        if (is_bool($value)) {
            return $value ? __('place_profile.history.yes') : __('place_profile.history.no');
        }

        if (is_array($value)) {
            return __('place_profile.history.changed');
        }

        return (string) $value;
    }

    private function featureChanges(Collection $requests): Collection
    {
        $groups = $requests->where('target_table', 'place_features');
        if ($groups->isEmpty()) return collect();

        $featureId = null;
        foreach ($groups as $r) {
            if ($r->target_field === 'feature_id') $featureId = (int) $this->decode($r->proposed_value);
        }
        if (!$featureId && $groups->first()->target_record_id) {
            $featureId = (int) DB::table('place_features')->where('id', $groups->first()->target_record_id)->value('feature_id');
        }
        $label = $featureId ? $this->translatedName('features', $featureId) : __('place_profile.history.feature');
        $old = []; $new = [];
        foreach ($groups as $r) {
            if (in_array($r->target_field, ['feature_id', 'comment'], true)) continue;
            $old[$r->target_field] = $this->decode($r->original_value);
            $new[$r->target_field] = $this->decode($r->proposed_value);
        }
        return collect([$label.': '.$this->formatFeatureState($old).' → '.$this->formatFeatureState($new)]);
    }

    public function formatFeatureState(array $v): string
    {
        $parts = [];
        $status = $v['status'] ?? null;
        if ($status) $parts[] = match ($status) { 'yes','available','present' => __('place_profile.history.present'), 'no','unavailable','absent' => __('place_profile.history.absent'), 'unknown' => __('place_profile.unknown'), default => Str::headline((string)$status) };
        if (isset($v['value_number']) && $v['value_number'] !== null) $parts[] = rtrim(rtrim(number_format((float)$v['value_number'], 2, ',', '.'), '0'), ',').' '.($v['unit_key'] ?? '');
        if (!empty($v['value_text'])) $parts[] = (string)$v['value_text'];
        $meta = $v['metadata'] ?? null;
        if (is_string($meta)) $meta = json_decode($meta, true);
        if (is_array($meta)) foreach ($meta as $x) if (is_scalar($x) && $x !== '') $parts[] = (string)$x;
        return $parts ? implode(', ', array_unique($parts)) : __('place_profile.unknown');
    }

    private function fieldLabel(string $table, string $field, mixed $old, mixed $new): string
    {
        $labels = [
            'places.name' => __('place_profile.history.fields.name'),
            'places.place_type_id' => __('place_profile.type'),
            'places.opening_status' => __('place_profile.operation'),
            'places.legal_status' => __('place_profile.legal_status'),
            'place_details.operator_name' => __('place_profile.operator'),
            'place_details.pitch_count' => __('place_profile.pitches'),
            'place_details.minimum_stay_nights' => __('place_profile.minimum_stay_nights'),
            'place_details.pitch_area_min_m2' => __('place_profile.pitch_area_min_m2'),
            'place_vehicle_types.vehicle_type_id' => __('place_profile.suitable_for'),
            'place_translations.description' => __('place_profile.history.fields.description'),
            'place_translations.directions' => __('place_profile.history.fields.directions'),
            'place_translations.access_information' => __('place_profile.history.fields.access'),
        ];
        return $labels[$table.'.'.$field] ?? Str::headline(str_replace('_', ' ', $field));
    }

    private function value(string $table, string $field, mixed $value): string
    {
        if (is_array($value) && array_key_exists('value', $value)) $value = $value['value'];
        if ($value === null || $value === '') return __('place_profile.unknown');
        if ($table === 'places' && $field === 'place_type_id') return $this->translatedName('place_types', (int)$value);
        if ($table === 'places' && $field === 'opening_status') return match ((string) $value) {
            'unclear' => __('place_profile.unknown'),
            'open' => __('ui.browse.operating_status.active'),
            'temporarily_closed' => __('ui.browse.operating_status.temporarily_closed'),
            'seasonally_closed' => __('ui.browse.operating_status.seasonally_closed'),
            'permanently_closed' => __('ui.browse.operating_status.permanently_closed'),
            default => Str::headline((string) $value),
        };
        if ($table === 'places' && $field === 'legal_status') {
            $labels = __('place_editing.info.legal_status_options');
            return is_array($labels) ? ($labels[(string) $value] ?? Str::headline((string) $value)) : Str::headline((string) $value);
        }
        if ($table === 'place_vehicle_types' && $field === 'vehicle_type_id') return $this->translatedName('vehicle_types', (int)$value);
        if (is_bool($value)) return $value ? __('place_profile.history.yes') : __('place_profile.history.no');
        if (is_array($value)) return __('place_profile.history.changed');
        return (string)$value;
    }

    private function safeHttpUrl(string $url): ?string
    {
        if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    private function translatedName(string $entityType, int $id): string
    {
        $locale = app()->getLocale(); $fallback = LocaleConfiguration::fallback();
        return (string) (DB::table('translations')->whereIn('entity_type', [$entityType, rtrim($entityType, 's')])->where('entity_id',$id)->where('field','name')->where('is_active',true)->whereIn('locale',[$locale,$fallback])->orderByRaw('CASE WHEN locale = ? THEN 0 ELSE 1 END',[$locale])->value('value') ?: '#'.$id);
    }

    private function decode(mixed $v): mixed { return $v === null ? null : json_decode((string)$v, true); }
}
