<?php

namespace App\Http\Controllers;

use App\Services\PlaceSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Validation\Rule;

class PlaceSubmissionController extends Controller
{
    public function create(): View
    {
        $placeTypes = DB::table('place_types')
            ->where('is_active', true)
            ->where('is_searchable', true)
            ->orderBy('sort_order')
            ->get(['id', 'slug']);

        $countries = DB::table('countries as c')
            ->leftJoin('country_translations as ct', function ($join): void {
                $join->on('ct.country_code', '=', 'c.code')
                    ->where('ct.locale', app()->getLocale());
            })
            ->where('c.is_active', true)
            ->orderBy('c.sort_order')
            ->orderBy('c.local_name')
            ->get([
                'c.code',
                DB::raw('COALESCE(ct.name, c.local_name) as name'),
            ]);

        if ($countries->isEmpty()) {
            $countries = collect([(object) [
                'code' => 'DE',
                'name' => app()->getLocale() === 'de' ? 'Deutschland' : 'Germany',
            ]]);
        }

        return view('places.create', compact('placeTypes', 'countries'));
    }

    public function store(Request $request, PlaceSubmissionService $submissions): RedirectResponse
    {
        $data = $request->validate([
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
            'target_audience' => ['required', 'array', 'min:1'],
            'target_audience.*' => [Rule::in(['mm', 'mf'])],
            'has_gloryhole' => ['nullable', 'boolean'],
            'street_address' => ['nullable', 'string', 'max:320'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        ]);

        $submissions->submit($request->user(), $data);

        return redirect()
            ->route('dashboard')
            ->with('place_submission_success', [
                'name' => trim($data['name']),
            ]);
    }
}
