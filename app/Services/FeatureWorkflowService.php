<?php

namespace App\Services;

use App\Support\LocaleConfiguration;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FeatureWorkflowService
{
    public function workflowsForPlace(int $placeId, ?string $locale = null): Collection
    {
        $locale ??= app()->getLocale();
        $fallback = LocaleConfiguration::fallback();
        $placeTypeId = DB::table('places')->where('id', $placeId)->value('place_type_id');
        $hasVisibilityCatalog = $placeTypeId && DB::table('feature_place_types')->where('place_type_id', $placeTypeId)->exists();

        $rows = DB::table('feature_workflows as fw')
            ->join('features as f', 'f.id', '=', 'fw.feature_id')
            ->join('feature_categories as fc', 'fc.id', '=', 'f.category_id')
            ->leftJoin('feature_place_types as fpt', function ($join) use ($placeTypeId) {
                $join->on('fpt.feature_id', '=', 'f.id')
                    ->where('fpt.place_type_id', $placeTypeId);
            })
            ->leftJoin('translations as ft', function ($join) use ($locale) {
                $join->on('ft.entity_id', '=', 'f.id')
                    ->where('ft.entity_type', 'feature')
                    ->where('ft.locale', $locale)
                    ->where('ft.field', 'name')
                    ->where('ft.is_active', true);
            })
            ->leftJoin('translations as fte', function ($join) use ($fallback) {
                $join->on('fte.entity_id', '=', 'f.id')
                    ->where('fte.entity_type', 'feature')
                    ->where('fte.locale', $fallback)
                    ->where('fte.field', 'name')
                    ->where('fte.is_active', true);
            })
            ->leftJoin('translations as ct', function ($join) use ($locale) {
                $join->on('ct.entity_id', '=', 'fc.id')
                    ->where('ct.entity_type', 'feature_category')
                    ->where('ct.locale', $locale)
                    ->where('ct.field', 'name')
                    ->where('ct.is_active', true);
            })
            ->leftJoin('translations as cte', function ($join) use ($fallback) {
                $join->on('cte.entity_id', '=', 'fc.id')
                    ->where('cte.entity_type', 'feature_category')
                    ->where('cte.locale', $fallback)
                    ->where('cte.field', 'name')
                    ->where('cte.is_active', true);
            })
            ->where('fw.is_active', true)
            ->where('f.is_active', true)
            ->where('fc.is_active', true)
            ->when($hasVisibilityCatalog, fn ($query) => $query->where('fpt.visibility', '!=', 'hidden'))
            ->orderBy('fc.sort_order')
            ->orderBy('fw.sort_order')
            ->orderBy('f.sort_order')
            ->get([
                'fw.id as workflow_id', 'fw.config', 'fw.sort_order as workflow_sort_order',
                'f.id as feature_id', 'f.slug as feature_slug',
                'fc.id as category_id', 'fc.slug as category_slug', 'fc.sort_order as category_sort_order',
                DB::raw("COALESCE(fpt.visibility, 'extended') as visibility"),
                DB::raw('COALESCE(ft.value, fte.value, f.slug) as feature_label'),
                DB::raw('COALESCE(ct.value, cte.value, fc.slug) as category_label'),
            ]);

        $existing = DB::table('place_features as pf')
            ->where('pf.place_id', $placeId)
            ->where('pf.is_active', true)
            ->whereNull('pf.valid_until')
            ->get(['pf.id', 'pf.feature_id', 'pf.status', 'pf.metadata'])
            ->keyBy('feature_id');

        $notes = DB::table('place_feature_notes as pfn')
            ->join('place_features as pf', 'pf.id', '=', 'pfn.place_feature_id')
            ->where('pf.place_id', $placeId)
            ->where('pf.is_active', true)
            ->whereNull('pf.valid_until')
            ->where('pfn.is_active', true)
            ->orderByRaw('CASE WHEN pfn.locale = ? THEN 0 WHEN pfn.locale = ? THEN 1 ELSE 2 END', [$locale, $fallback])
            ->get(['pf.feature_id', 'pfn.note'])
            ->unique('feature_id')
            ->pluck('note', 'feature_id');

        return $rows->map(function ($row) use ($existing, $notes, $locale) {
            $config = json_decode($row->config, true) ?: [];
            $current = $existing->get($row->feature_id);
            $metadata = $current && $current->metadata ? (json_decode($current->metadata, true) ?: []) : [];

            return (object) [
                'workflow_id' => $row->workflow_id,
                'feature_id' => (int) $row->feature_id,
                'feature_slug' => $row->feature_slug,
                'feature_label' => $row->feature_label,
                'category_id' => (int) $row->category_id,
                'category_slug' => $row->category_slug,
                'category_label' => $row->category_label,
                'visibility' => $row->visibility,
                'config' => $config,
                'status' => $current->status ?? 'unknown',
                'metadata' => $metadata,
                'comment' => $notes->get($row->feature_id),
                'locale' => $locale,
            ];
        });
    }

    public function groupedForPlace(int $placeId, ?string $locale = null): Collection
    {
        return $this->workflowsForPlace($placeId, $locale)
            ->groupBy(fn ($workflow) => $workflow->category_id)
            ->map(fn (Collection $items) => (object) [
                'id' => $items->first()->category_id,
                'slug' => $items->first()->category_slug,
                'label' => $items->first()->category_label,
                'features' => $items->values(),
            ])
            ->values();
    }

    public function validate(Request $request, Collection $workflows): array
    {
        $rules = ['features' => ['nullable', 'array']];

        foreach ($workflows as $workflow) {
            $id = $workflow->feature_id;
            $statusValues = collect($workflow->config['status_options'] ?? [])->pluck('value')->all();
            $rules["features.{$id}.status"] = ['nullable', 'string', Rule::in($statusValues)];
            $rules["features.{$id}.comment"] = ['nullable', 'string', 'max:1000'];

            foreach ($workflow->config['details'] ?? [] as $detail) {
                $key = $detail['key'];
                $base = "features.{$id}.details.{$key}";

                if ($detail['type'] === 'number') {
                    $rules[$base] = ['nullable', 'numeric', 'min:0', 'max:1000000'];
                } elseif ($detail['type'] === 'number_unit') {
                    $rules["{$base}.value"] = ['nullable', 'numeric', 'min:0', 'max:1000000'];
                    $rules["{$base}.unit"] = ['nullable', 'string', Rule::in(collect($detail['units'] ?? [])->pluck('value')->all())];
                } elseif ($detail['type'] === 'select') {
                    $rules[$base] = ['nullable', 'string', Rule::in(collect($detail['options'] ?? [])->pluck('value')->all())];
                } elseif ($detail['type'] === 'pricing') {
                    $rules["{$base}.status"] = ['nullable', 'string', Rule::in(['unknown', 'free', 'paid'])];
                    $rules["{$base}.amount"] = ['nullable', 'numeric', 'min:0', 'max:1000000'];
                    $rules["{$base}.currency"] = ['nullable', 'string', Rule::in(['EUR'])];
                    $rules["{$base}.quantity"] = ['nullable', 'numeric', 'min:0.01', 'max:1000000'];
                    $rules["{$base}.unit"] = ['nullable', 'string', Rule::in(collect($detail['units'] ?? [])->pluck('value')->all())];
                } elseif ($detail['type'] === 'money') {
                    $rules["{$base}.amount"] = ['nullable', 'numeric', 'min:0', 'max:1000000'];
                    $rules["{$base}.currency"] = ['nullable', 'string', Rule::in(['EUR'])];
                    $rules["{$base}.quantity"] = ['nullable', 'numeric', 'min:0.01', 'max:1000000'];
                    $rules["{$base}.unit"] = ['nullable', 'string', Rule::in(collect($detail['units'] ?? [])->pluck('value')->all())];
                }
            }
        }

        return Validator::make($request->all(), $rules)->validate();
    }

    public function normalizeFeatureData(object $workflow, array $validated): array
    {
        $featureData = $validated['features'][$workflow->feature_id] ?? [];
        $status = $featureData['status'] ?? 'unknown';

        return [
            'status' => $status,
            'metadata' => $this->normalizeDetails($workflow->config, $status, $featureData['details'] ?? []),
            'comment' => trim((string) ($featureData['comment'] ?? '')),
        ];
    }

    public function iconNameFor(object $workflow): string
    {
        return match ($workflow->feature_slug) {
            'wifi' => 'wifi',
            'electricity', 'ev-charging' => 'plug',
            'fresh-water', 'grey-water-disposal', 'black-water-disposal', 'floor-drain',
            'toilet', 'shower', 'washbasin' => 'droplet',
            'accessible-toilet', 'accessible-shower' => 'wheelchair',
            'entrance-height', 'entrance-width', 'max-vehicle-length' => 'ruler-measure',
            'max-vehicle-weight' => 'weight',
            'motorhome-access' => 'road',
            'distance-to-city-center' => 'map-pin',
            'distance-to-supermarket' => 'shopping-cart',
            'distance-to-public-transport' => 'bus',
            'overnight-fee', 'person-fee', 'tourist-tax' => 'currency-euro',
            'dogs-allowed' => 'dog',
            'reservation-required' => 'check',
            'max-stay-duration' => 'clock',
            'caravans-allowed' => 'caravan',
            'tents-allowed' => 'tent',
            'motorhomes-only' => 'caravan',
            default => match ($workflow->category_slug) {
                'utilities' => 'plug',
                'sanitary' => 'droplet',
                'accessibility' => 'wheelchair',
                'access' => 'road',
                'surroundings' => 'map-pin',
                'payment' => 'currency-euro',
                'suitability' => 'users-group',
                'costs' => 'currency-euro',
                'rules' => 'check',
                'fuel-rest-area' => 'gas-station',
                default => 'question-mark',
            },
        };
    }

    public function present(object $workflow, ?string $locale = null): object
    {
        $locale ??= app()->getLocale();
        $displayStatus = (string) ($workflow->display_status ?? $workflow->status);
        $statusOptions = collect($workflow->config['status_options'] ?? []);
        $statusOption = $statusOptions->firstWhere('value', $displayStatus);
        $statusLabel = $statusOption
            ? $this->localized($statusOption['label'] ?? [], $locale)
            : $displayStatus;

        $tone = match ($displayStatus) {
            'unavailable', 'no' => 'negative',
            'unknown' => 'unknown',
            default => 'positive',
        };

        $parts = [];
        foreach ($workflow->config['details'] ?? [] as $detail) {
            $key = $detail['key'] ?? null;
            if (! $key || ! array_key_exists($key, $workflow->metadata ?? [])) {
                continue;
            }

            $formatted = $this->formatDetailValue($detail, $workflow->metadata[$key], $locale);
            if ($formatted !== null && $formatted !== '') {
                $parts[] = $formatted;
            }
        }

        $workflow->display_status = $displayStatus;
        $workflow->status_label = $statusLabel;
        $workflow->tone = $tone;
        $workflow->detail_summary = implode(' · ', $parts);

        return $workflow;
    }

    public function saveDraft(int $placeId, int $userId, Collection $workflows, array $validated): void
    {
        $submitted = $validated['features'] ?? [];
        $now = now();
        $locale = app()->getLocale();

        foreach ($workflows as $workflow) {
            $featureData = $submitted[$workflow->feature_id] ?? [];
            $status = $featureData['status'] ?? 'unknown';
            $details = $this->normalizeDetails($workflow->config, $status, $featureData['details'] ?? []);
            $comment = trim((string) ($featureData['comment'] ?? ''));

            $existing = DB::table('place_features')
                ->where('place_id', $placeId)
                ->where('feature_id', $workflow->feature_id)
                ->where('is_active', true)
                ->whereNull('valid_until')
                ->first();

            if ($status === 'unknown' && $details === [] && $comment === '') {
                if ($existing) {
                    DB::table('place_feature_notes')->where('place_feature_id', $existing->id)->delete();
                    DB::table('place_features')->where('id', $existing->id)->delete();
                }

                continue;
            }

            $values = [
                'status' => $status,
                'metadata' => $details === [] ? null : json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_active' => true,
                'updated_at' => $now,
            ];

            if ($existing) {
                DB::table('place_features')->where('id', $existing->id)->update($values);
                $placeFeatureId = $existing->id;
            } else {
                $placeFeatureId = DB::table('place_features')->insertGetId($values + [
                    'place_id' => $placeId,
                    'feature_id' => $workflow->feature_id,
                    'feature_option_id' => null,
                    'value_number' => null,
                    'value_text' => null,
                    'unit_key' => null,
                    'unit_id' => null,
                    'rate_quantity' => null,
                    'rate_unit_id' => null,
                    'internal_comment' => 'Vom Ersteller im Entwurf gepflegt.',
                    'valid_from' => $now,
                    'valid_until' => null,
                    'created_at' => $now,
                ]);
            }

            DB::table('place_feature_notes')->where('place_feature_id', $placeFeatureId)->where('locale', $locale)->delete();
            if ($comment !== '') {
                DB::table('place_feature_notes')->insert([
                    'place_feature_id' => $placeFeatureId,
                    'locale' => $locale,
                    'note' => $comment,
                    'is_active' => true,
                    'internal_comment' => 'Vom Ersteller im Entwurf gepflegt.',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        DB::table('audit_logs')->insert([
            'user_id' => $userId,
            'entity_type' => 'place',
            'entity_id' => $placeId,
            'action' => 'place_draft_features_updated',
            'source' => 'user',
            'old_values' => null,
            'new_values' => json_encode(['workflow_features' => count($submitted)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'internal_comment' => null,
            'created_at' => $now,
        ]);
    }

    public function localized(array $value, string $locale): string
    {
        return (string) ($value[$locale] ?? $value[LocaleConfiguration::fallback()] ?? reset($value) ?? '');
    }

    private function formatDetailValue(array $detail, mixed $value, string $locale): ?string
    {
        $type = $detail['type'] ?? null;

        if ($type === 'number') {
            return $this->formatNumber($value).' '.($detail['unit'] ?? '');
        }

        if ($type === 'select') {
            $option = collect($detail['options'] ?? [])->firstWhere('value', $value);

            return $option ? $this->localized($option['label'] ?? [], $locale) : (string) $value;
        }

        if ($type === 'number_unit' && is_array($value)) {
            $unit = collect($detail['units'] ?? [])->firstWhere('value', $value['unit'] ?? null);
            $unitLabel = $unit ? $this->localized($unit['label'] ?? [], $locale) : ($value['unit'] ?? '');

            return trim($this->formatNumber($value['value'] ?? null).' '.$unitLabel);
        }

        if (($type === 'pricing' || $type === 'money') && is_array($value)) {
            $priceStatus = $value['status'] ?? ($type === 'money' ? 'paid' : 'unknown');
            if ($priceStatus === 'unknown') {
                return null;
            }
            if ($priceStatus === 'free') {
                return __('place_profile.price_status.free');
            }

            if (($value['amount'] ?? '') === '') {
                return null;
            }

            $currency = ($value['currency'] ?? 'EUR') === 'EUR' ? '€' : (string) ($value['currency'] ?? '');
            $text = $this->formatNumber($value['amount']).' '.$currency;
            $unitKey = $value['unit'] ?? null;

            if ($unitKey) {
                $unit = collect($detail['units'] ?? [])->firstWhere('value', $unitKey);
                $unitLabel = $unit ? $this->localized($unit['label'] ?? [], $locale) : (string) $unitKey;
                $quantity = $value['quantity'] ?? 1;
                $text .= ' / '.(((float) $quantity === 1.0) ? '' : $this->formatNumber($quantity).' ').$unitLabel;
            }

            return $text;
        }

        return null;
    }

    private function formatNumber(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $number = (float) $value;
        $decimals = floor($number) === $number ? 0 : 2;

        return \Illuminate\Support\Number::format($number, $decimals, $decimals, app()->getLocale());
    }

    private function normalizeDetails(array $config, string $status, array $input): array
    {
        $output = [];

        foreach ($config['details'] ?? [] as $detail) {
            if (! in_array($status, $detail['show_for'] ?? [], true)) {
                continue;
            }

            $key = $detail['key'];
            $value = $input[$key] ?? null;

            if ($detail['type'] === 'number' || $detail['type'] === 'select') {
                if ($value !== null && $value !== '') {
                    $output[$key] = $value;
                }
            } elseif ($detail['type'] === 'number_unit' && is_array($value)) {
                if (($value['value'] ?? '') !== '') {
                    $output[$key] = ['value' => $value['value'], 'unit' => $value['unit'] ?? null];
                }
            } elseif (($detail['type'] === 'pricing' || $detail['type'] === 'money') && is_array($value)) {
                if ($detail['type'] === 'pricing') {
                    $priceStatus = $value['status'] ?? 'unknown';
                    if ($priceStatus === 'unknown') {
                        $output[$key] = ['status' => 'unknown'];

                        continue;
                    }
                    if ($priceStatus === 'free') {
                        $output[$key] = ['status' => 'free'];

                        continue;
                    }
                }

                if (($value['amount'] ?? '') !== '') {
                    $output[$key] = [
                        'status' => 'paid',
                        'amount' => $value['amount'],
                        'currency' => $value['currency'] ?? 'EUR',
                        'quantity' => $value['quantity'] ?? 1,
                        'unit' => $value['unit'] ?? null,
                    ];
                }
            }
        }

        return $output;
    }
}
