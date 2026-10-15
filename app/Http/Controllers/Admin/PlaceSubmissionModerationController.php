<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UserNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlaceSubmissionModerationController extends Controller
{
    public function index(): View
    {
        $places = DB::table('places as p')
            ->join('place_types as pt', 'pt.id', '=', 'p.place_type_id')
            ->leftJoin('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->where('p.publication_status', 'pending')
            ->orderBy('p.created_at')
            ->paginate(30, [
                'p.id', 'p.name', 'p.created_at', 'p.environment', 'p.has_gloryhole',
                'pt.slug as place_type_slug', 'pa.city', 'pa.postal_code', 'u.name as creator_name',
            ]);

        return view('admin.place-submissions.index', compact('places'));
    }

    public function show(int $place): View
    {
        $item = $this->pendingPlace($place);

        $placeTypes = DB::table('place_types')
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug']);

        return view('admin.place-submissions.show', [
            'place' => $item,
            'placeTypes' => $placeTypes,
        ]);
    }

    public function approve(
        Request $request,
        int $place,
        UserNotificationService $notifications,
    ): RedirectResponse {
        $data = $this->validatePlace($request);

        DB::transaction(function () use ($request, $place, $data, $notifications): void {
            $current = $this->pendingPlace($place, true);
            $now = now();

            DB::table('places')->where('id', $place)->update([
                'place_type_id' => (int) $data['place_type_id'],
                'name' => trim($data['name']),
                'description' => $this->nullableTrim($data['description'] ?? null),
                'environment' => $data['environment'] ?? null,
                'target_audience' => $data['target_audience'],
                'has_gloryhole' => array_key_exists('has_gloryhole', $data) && $data['has_gloryhole'] !== null
                    ? (bool) $data['has_gloryhole']
                    : null,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'publication_status' => 'published',
                'approved_by' => $request->user()->id,
                'approved_at' => $now,
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

            DB::table('place_addresses')
                ->where('place_id', $place)
                ->where('is_active', true)
                ->whereNull('version_valid_until')
                ->delete();

            $address = [
                'country_code' => isset($data['country_code']) && $data['country_code'] !== null
                    ? strtoupper(trim((string) $data['country_code']))
                    : null,
                'postal_code' => $this->nullableTrim($data['postal_code'] ?? null),
                'city' => $this->nullableTrim($data['city'] ?? null),
                'street' => $this->nullableTrim($data['street'] ?? null),
                'house_number' => $this->nullableTrim($data['house_number'] ?? null),
            ];

            if (collect($address)->contains(fn ($value) => $value !== null)) {
                DB::table('place_addresses')->insert([
                    'place_id' => $place,
                    'country_code' => $address['country_code'],
                    'postal_code' => $address['postal_code'],
                    'city' => $address['city'],
                    'street' => $address['street'],
                    'house_number' => $address['house_number'],
                    'address_addition' => null,
                    'is_active' => true,
                    'version_valid_from' => $now,
                    'version_valid_until' => null,
                    'internal_comment' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            if ($current->created_by) {
                $locale = $notifications->userLocale((int) $current->created_by);

                $notifications->createImmediate(
                    (int) $current->created_by,
                    'place_submission_approved',
                    __('notifications.place_submission_approved_title', [], $locale),
                    __('notifications.place_submission_approved_message', ['place' => $data['name']], $locale),
                    route('places.show', $current->slug),
                    'normal',
                    'check',
                    ['place_name' => $data['name'], 'decision' => 'approved'],
                    (int) $request->user()->id,
                );
            }
        });

        return redirect()
            ->route('admin.place-submissions.index')
            ->with('ui_toast', __('admin.place_submissions.approved'));
    }

    public function reject(
        Request $request,
        int $place,
        UserNotificationService $notifications,
    ): RedirectResponse {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($request, $place, $data, $notifications): void {
            $current = $this->pendingPlace($place, true);

            if ($current->created_by) {
                $locale = $notifications->userLocale((int) $current->created_by);

                $notifications->createImmediate(
                    (int) $current->created_by,
                    'place_submission_rejected',
                    __('notifications.place_submission_rejected_title', [], $locale),
                    __('notifications.place_submission_rejected_message', [
                        'place' => $current->name,
                        'reason' => trim($data['reason']),
                    ], $locale),
                    route('notifications.index'),
                    'normal',
                    'x',
                    [
                        'place_name' => $current->name,
                        'decision' => 'rejected',
                        'reason' => trim($data['reason']),
                    ],
                    (int) $request->user()->id,
                );
            }

            DB::table('places')->where('id', $place)->delete();
        });

        return redirect()
            ->route('admin.place-submissions.index')
            ->with('ui_toast', __('admin.place_submissions.rejected'));
    }

    private function validatePlace(Request $request): array
    {
        return $request->validate([
            'place_type_id' => [
                'required',
                'integer',
                Rule::exists('place_types', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('is_searchable', true),
                ),
            ],
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'environment' => ['nullable', Rule::in(['indoor', 'outdoor', 'both'])],
            'target_audience' => ['required', Rule::in(['mm', 'mf'])],
            'has_gloryhole' => ['nullable', 'boolean'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        ]);
    }

    private function pendingPlace(int $place, bool $lock = false): object
    {
        $query = DB::table('places as p')
            ->leftJoin('place_addresses as pa', function ($join): void {
                $join->on('pa.place_id', '=', 'p.id')
                    ->where('pa.is_active', true)
                    ->whereNull('pa.version_valid_until');
            })
            ->leftJoin('users as u', 'u.id', '=', 'p.created_by')
            ->where('p.id', $place)
            ->where('p.publication_status', 'pending');

        if ($lock) {
            $query->lockForUpdate();
        }

        $item = $query->first([
            'p.id', 'p.name', 'p.slug', 'p.place_type_id', 'p.description', 'p.environment',
            'p.target_audience', 'p.has_gloryhole', 'p.latitude', 'p.longitude', 'p.created_by', 'p.created_at',
            'pa.country_code', 'pa.postal_code', 'pa.city', 'pa.street', 'pa.house_number',
            'u.name as creator_name',
        ]);

        abort_unless($item, 404);

        return $item;
    }

    private function nullableTrim(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }
}
