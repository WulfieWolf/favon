<?php

namespace App\Http\Controllers;

use App\Services\PlaceSubmissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlaceSubmissionController extends Controller
{
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
            'has_gloryhole' => ['nullable', 'boolean'],
            'street' => ['nullable', 'string', 'max:255'],
            'house_number' => ['nullable', 'string', 'max:32'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'city' => ['nullable', 'string', 'max:255'],
            'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Za-z]{2}$/'],
        ]);

        $submissions->submit($request->user(), $data);

        return redirect()
            ->route('dashboard')
            ->with('ui_toast', __('places.submission_received'));
    }
}
