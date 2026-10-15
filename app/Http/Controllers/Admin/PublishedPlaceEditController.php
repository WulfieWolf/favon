<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PublishedPlaceEditController extends Controller
{
    public function edit(Request $request, int $place, PermissionService $permissions): View
    {
        abort_unless($permissions->isOwner($request->user()), 403);

        $item = $this->publishedPlace($place);
        $placeTypes = DB::table('place_types')->where('is_active', true)->where('is_searchable', true)
            ->orderBy('sort_order')->get(['id', 'slug']);

        return view('admin.place-submissions.show', [
            'place' => $item,
            'placeTypes' => $placeTypes,
            'editingPublished' => true,
        ]);
    }

    public function update(Request $request, int $place, PermissionService $permissions): RedirectResponse
    {
        abort_unless($permissions->isOwner($request->user()), 403);

        $data = $request->validate([
            'place_type_id' => ['required', 'integer', Rule::exists('place_types', 'id')->where(
                fn ($query) => $query->where('is_active', true)->where('is_searchable', true),
            )],
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'environment' => ['nullable', Rule::in(['indoor', 'outdoor', 'both'])],
            'target_audience' => ['required', 'array', 'min:1'],
            'target_audience.*' => [Rule::in(['mm', 'mf'])],
            'has_gloryhole' => ['nullable', 'boolean'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        ]);

        $slug = DB::transaction(function () use ($place, $data): string {
            $current = $this->publishedPlace($place, true);
            $now = now();
            $normalize = static function (mixed $value): ?string {
                $trimmed = trim((string) ($value ?? ''));
                return $trimmed === '' ? null : $trimmed;
            };

            DB::table('places')->where('id', $place)->update([
                'place_type_id' => (int) $data['place_type_id'],
                'name' => trim($data['name']),
                'description' => $normalize($data['description'] ?? null),
                'environment' => $data['environment'] ?? null,
                'target_audience' => null,
                'target_mm' => in_array('mm', $data['target_audience'], true),
                'target_mf' => in_array('mf', $data['target_audience'], true),
                'has_gloryhole' => array_key_exists('has_gloryhole', $data) && $data['has_gloryhole'] !== null
                    ? (bool) $data['has_gloryhole'] : null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'updated_at' => $now,
            ]);

            DB::table('place_place_types')->where('place_id', $place)->delete();
            DB::table('place_place_types')->insert([
                'place_id' => $place,
                'place_type_id' => (int) $data['place_type_id'],
                'is_primary' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Keep past address versions instead of deleting the previous active version.
            DB::table('place_addresses')->where('place_id', $place)
                ->where('is_active', true)->whereNull('version_valid_until')
                ->update(['version_valid_until' => $now, 'updated_at' => $now]);

            $address = [
                'country_code' => $normalize($data['country_code'] ?? null)
                    ? strtoupper($normalize($data['country_code'])) : null,
                'postal_code' => $normalize($data['postal_code'] ?? null),
                'city' => $normalize($data['city'] ?? null),
                'street' => $normalize($data['street'] ?? null),
                'house_number' => $normalize($data['house_number'] ?? null),
            ];

            if (collect($address)->contains(fn ($value) => $value !== null)) {
                DB::table('place_addresses')->insert(array_merge($address, [
                    'place_id' => $place,
                    'address_addition' => $current->address_addition,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }

            return $current->slug;
        });

        return redirect()->route('places.show', $slug)
            ->with('ui_toast', __('places.admin_edit.saved'));
    }

    private function publishedPlace(int $place, bool $lock = false): object
    {
        $query = DB::table('places as p')
            ->leftJoin('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)->whereNull('pa.version_valid_until');
            })
            ->where('p.id', $place)
            ->where('p.is_active', true)
            ->where('p.publication_status', 'published');

        if ($lock) {
            $query->lockForUpdate();
        }

        $item = $query->first([
            'p.id', 'p.name', 'p.slug', 'p.place_type_id', 'p.description',
            'p.environment', 'p.target_mm', 'p.target_mf', 'p.has_gloryhole',
            'p.latitude', 'p.longitude', 'pa.country_code', 'pa.postal_code',
            'pa.city', 'pa.street', 'pa.house_number', 'pa.address_addition',
        ]);

        abort_unless($item, 404);

        return $item;
    }
}
