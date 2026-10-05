<?php

use App\Models\Photo;
use App\Models\UserProfile;
use App\Services\BadgeService;
use App\Services\PublicHandleService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('community_profile.settings.title')] class extends Component {
    use WithFileUploads;

    public string $publicHandle = '';
    public string $automaticHandle = '';
    public bool $handleFinalized = false;
    public string $newHandle = '';
    public bool $handleCheckOpen = false;
    public bool $handleCheckAvailable = false;
    public string $handleCheckMessage = '';

    public string $bio = '';
    public string $hometownCity = '';
    public string $hometownCountryCode = '';
    public string $hometownLatitude = '';
    public string $hometownLongitude = '';
    public string $hometownSourceId = '';
    public string $birthDate = '';
    public string $gender = 'none';
    public string $genderCustom = '';
    public string $vehicleType = '';
    public string $vehicleDetails = '';
    public array $vehicleOptions = [];

    public string $profilePhotoVisibility = 'public';
    public string $bioVisibility = 'registered';
    public string $hometownVisibility = 'registered';
    public string $ageVisibility = 'private';
    public string $genderVisibility = 'private';
    public string $vehicleVisibility = 'registered';
    public bool $showGamification = true;
    public bool $showJoinDate = true;
    public int $gamificationLevel = 1;
    public string $selectedBadgeId = '';
    public array $titleOptions = [];
    public array $visibilityValues = ['public', 'registered', 'private'];
    public $profilePhoto = null;
    public ?string $currentProfilePhotoUrl = null;

    public function mount(): void
    {
        $this->loadProfile();
    }

    public function saveProfile(): void
    {
        $this->persistProfile();
        $this->loadProfile();

        Flux::toast(variant: 'success', text: __('community_profile.settings.saved'));
    }

    public function updatedNewHandle(): void
    {
        $this->handleCheckOpen = false;
        $this->handleCheckAvailable = false;
        $this->handleCheckMessage = '';
        $this->resetErrorBag('newHandle');
    }

    public function checkHandle(): void
    {
        $profile = $this->profileRecord();

        if ($profile->alias_finalized_at) {
            $this->handleCheckOpen = true;
            $this->handleCheckAvailable = false;
            $this->handleCheckMessage = __('community_profile.settings.handle_final_note');

            return;
        }

        $validator = Validator::make(
            ['newHandle' => $this->newHandle],
            ['newHandle' => $this->handleRules($profile)],
        );

        $this->handleCheckOpen = true;

        if ($validator->fails()) {
            $this->handleCheckAvailable = false;
            $this->handleCheckMessage = $validator->errors()->first('newHandle');

            return;
        }

        $this->handleCheckAvailable = true;
        $this->handleCheckMessage = __('community_profile.settings.handle_available');
    }

    public function finalizeHandle()
    {
        $profile = $this->profileRecord();

        if ($profile->alias_finalized_at) {
            $this->handleCheckOpen = true;
            $this->handleCheckAvailable = false;
            $this->handleCheckMessage = __('community_profile.settings.handle_final_note');

            return;
        }

        $validator = Validator::make(
            ['newHandle' => $this->newHandle],
            ['newHandle' => $this->handleRules($profile)],
        );

        if ($validator->fails()) {
            $this->handleCheckOpen = true;
            $this->handleCheckAvailable = false;
            $this->handleCheckMessage = $validator->errors()->first('newHandle');

            return;
        }

        // Save all currently edited profile fields first so finalizing the
        // permanent handle never discards changes made on the same page.
        // Close the confirmation overlay first so validation errors remain visible.
        $this->handleCheckOpen = false;
        $this->persistProfile();

        $newHandle = $validator->validated()['newHandle'];

        $profile->update([
            'public_alias' => $newHandle,
            'alias_finalized_at' => now(),
        ]);

        session()->flash('ui_toast', __('community_profile.settings.handle_saved'));

        // A full redirect is intentional: the user menu and all profile URLs
        // must immediately use the new permanent handle.
        return redirect()->route('community-profile.edit');
    }

    public function closeHandleCheck(): void
    {
        $this->handleCheckOpen = false;
    }

    public function saveProfilePhoto(): void
    {
        $this->validate([
            'profilePhoto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $user = Auth::user();
        $oldPhotoId = $user->profile_photo_id;
        $path = $this->profilePhoto->store('profile-photos/'.$user->id, 'local');
        $dimensions = @getimagesize($this->profilePhoto->getRealPath());

        $photo = Photo::create([
            'user_id' => $user->id,
            'storage_path' => $path,
            'original_filename' => $this->profilePhoto->getClientOriginalName(),
            'mime_type' => $this->profilePhoto->getMimeType(),
            'file_size' => $this->profilePhoto->getSize(),
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'status' => 'approved',
            'is_active' => true,
            'internal_comment' => 'Vom Nutzer als Profilbild hochgeladen.',
        ]);

        $user->update(['profile_photo_id' => $photo->id]);

        if ($oldPhotoId) {
            $this->deleteUnusedProfilePhoto((int) $oldPhotoId, (int) $user->id);
        }

        $this->profilePhoto = null;
        $this->loadProfile();

        Flux::toast(variant: 'success', text: __('community_profile.settings.photo_saved'));
    }

    public function removeProfilePhoto(): void
    {
        $user = Auth::user();
        $oldPhotoId = $user->profile_photo_id;
        $user->update(['profile_photo_id' => null]);

        if ($oldPhotoId) {
            $this->deleteUnusedProfilePhoto((int) $oldPhotoId, (int) $user->id);
        }

        $this->currentProfilePhotoUrl = null;
        Flux::toast(variant: 'success', text: __('community_profile.settings.photo_removed'));
    }

    public function clearHometown(): void
    {
        $this->hometownCity = '';
        $this->hometownCountryCode = '';
        $this->hometownLatitude = '';
        $this->hometownLongitude = '';
        $this->hometownSourceId = '';
    }

    private function loadProfile(): void
    {
        $user = Auth::user()->fresh();
        $profile = $this->profileRecord();
        $settings = DB::table('user_settings')->where('user_id', $user->id)->first();

        $this->automaticHandle = $profile->public_handle;
        $this->publicHandle = $profile->public_alias ?: $profile->public_handle;
        $this->handleFinalized = $profile->alias_finalized_at !== null;
        $this->bio = $profile->bio ?? '';
        $this->hometownCity = $profile->hometown_city ?? '';
        $this->hometownCountryCode = $profile->hometown_country_code ?? '';
        $this->hometownLatitude = $profile->hometown_latitude !== null ? (string) $profile->hometown_latitude : '';
        $this->hometownLongitude = $profile->hometown_longitude !== null ? (string) $profile->hometown_longitude : '';
        $this->hometownSourceId = $profile->hometown_source_id ?? '';
        $this->birthDate = $profile->birth_date?->format('Y-m-d') ?? '';
        $this->gender = $profile->gender ?? 'none';
        $this->genderCustom = $profile->gender_custom ?? '';
        $this->vehicleType = $profile->vehicle_type ?? '';
        $this->vehicleDetails = $profile->vehicle_details ?? '';

        $this->vehicleOptions = DB::table('vehicle_types as vt')
            ->leftJoin('translations as tr', function ($join) {
                $join->on('tr.entity_id', '=', 'vt.id')
                    ->where('tr.entity_type', 'vehicle_type')
                    ->where('tr.field', 'name')
                    ->where('tr.locale', app()->getLocale())
                    ->where('tr.is_active', true);
            })
            ->leftJoin('translations as en', function ($join) {
                $join->on('en.entity_id', '=', 'vt.id')
                    ->where('en.entity_type', 'vehicle_type')
                    ->where('en.field', 'name')
                    ->where('en.locale', 'en')
                    ->where('en.is_active', true);
            })
            ->where('vt.is_active', true)
            ->orderBy('vt.sort_order')
            ->get([
                'vt.slug as vehicle_slug',
                DB::raw('COALESCE(tr.value, en.value, vt.slug) as vehicle_label'),
            ])
            ->mapWithKeys(fn ($row) => [(string) $row->vehicle_slug => (string) $row->vehicle_label])
            ->all();

        $this->vehicleOptions['other'] = __('community_profile.vehicle_values.other');

        $this->profilePhotoVisibility = $settings?->profile_photo_visibility ?? 'public';
        $this->bioVisibility = $settings?->bio_visibility ?? 'registered';
        $this->hometownVisibility = $settings?->hometown_visibility ?? 'registered';
        $this->ageVisibility = $settings?->age_visibility ?? 'private';
        $this->genderVisibility = $settings?->gender_visibility ?? 'private';
        $this->vehicleVisibility = $settings?->vehicle_visibility ?? 'registered';
        $this->showGamification = (bool) ($settings?->show_gamification ?? true);
        $this->showJoinDate = (bool) ($settings?->show_join_date ?? true);
        $this->gamificationLevel = app(\App\Services\XpService::class)->summaryForUser((int) $user->id)['level'];
        $this->titleOptions = app(BadgeService::class)->selectableTitles((int) $user->id);
        $this->selectedBadgeId = $profile->selected_badge_id ? (string) $profile->selected_badge_id : '';
        $this->currentProfilePhotoUrl = null;
        if ($user->profile_photo_id) {
            $photoPath = DB::table('photos')
                ->where('id', $user->profile_photo_id)
                ->where('is_active', true)
                ->value('storage_path');

            if ($photoPath) {
                $this->currentProfilePhotoUrl = route('users.profile.photo', $profile->public_alias ?: $profile->public_handle);
            }
        }
    }

    private function persistProfile(): void
    {
        $user = Auth::user();
        $profile = $this->profileRecord();

        $validated = $this->validate([
            'bio' => ['nullable', 'string', 'max:500'],
            'hometownCity' => ['nullable', 'string', 'max:120'],
            'hometownCountryCode' => ['nullable', 'required_with:hometownCity', 'string', 'size:2'],
            'hometownLatitude' => ['nullable', 'required_with:hometownCity', 'numeric', 'between:-90,90'],
            'hometownLongitude' => ['nullable', 'required_with:hometownCity', 'numeric', 'between:-180,180'],
            'hometownSourceId' => ['nullable', 'string', 'max:100'],
            'birthDate' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['male', 'female', 'nonbinary_other', 'none'])],
            'genderCustom' => ['nullable', 'string', 'max:80'],
            'vehicleType' => ['nullable', Rule::in(array_keys($this->vehicleOptions))],
            'vehicleDetails' => ['nullable', 'string', 'max:160'],
            'profilePhotoVisibility' => ['required', Rule::in($this->visibilityValues)],
            'bioVisibility' => ['required', Rule::in($this->visibilityValues)],
            'hometownVisibility' => ['required', Rule::in($this->visibilityValues)],
            'ageVisibility' => ['required', Rule::in($this->visibilityValues)],
            'genderVisibility' => ['required', Rule::in($this->visibilityValues)],
            'vehicleVisibility' => ['required', Rule::in($this->visibilityValues)],
            'showGamification' => ['boolean'],
            'showJoinDate' => ['boolean'],
            'selectedBadgeId' => [
                'nullable',
                Rule::in(array_map('strval', array_keys($this->titleOptions))),
            ],
        ]);

        DB::transaction(function () use ($user, $profile, $validated): void {
            $profile->update([
                'bio' => filled($validated['bio']) ? trim($validated['bio']) : null,
                'hometown_city' => filled($validated['hometownCity']) ? trim($validated['hometownCity']) : null,
                'hometown_country_code' => filled($validated['hometownCountryCode']) ? strtoupper($validated['hometownCountryCode']) : null,
                'hometown_latitude' => filled($validated['hometownLatitude']) ? $validated['hometownLatitude'] : null,
                'hometown_longitude' => filled($validated['hometownLongitude']) ? $validated['hometownLongitude'] : null,
                'hometown_source_id' => filled($validated['hometownSourceId']) ? $validated['hometownSourceId'] : null,
                'birth_date' => filled($validated['birthDate']) ? $validated['birthDate'] : null,
                'gender' => $validated['gender'],
                'gender_custom' => $validated['gender'] === 'nonbinary_other' && filled($validated['genderCustom'])
                    ? trim($validated['genderCustom'])
                    : null,
                'vehicle_type' => filled($validated['vehicleType']) ? $validated['vehicleType'] : null,
                'vehicle_details' => filled($validated['vehicleDetails']) ? trim($validated['vehicleDetails']) : null,
                'selected_badge_id' => filled($validated['selectedBadgeId']) ? (int) $validated['selectedBadgeId'] : null,
            ]);

            DB::table('user_settings')
                ->where('user_id', $user->id)
                ->update([
                    'profile_photo_visibility' => $validated['profilePhotoVisibility'],
                    'bio_visibility' => $validated['bioVisibility'],
                    'hometown_visibility' => $validated['hometownVisibility'],
                    'age_visibility' => $validated['ageVisibility'],
                    'gender_visibility' => $validated['genderVisibility'],
                    'vehicle_visibility' => $validated['vehicleVisibility'],
                    'show_gamification' => $validated['showGamification'],
                    'show_join_date' => $validated['showJoinDate'],
                    'updated_at' => now(),
                ]);
        });
    }

    private function handleRules(UserProfile $profile): array
    {
        return [
            'required',
            'string',
            'min:3',
            'max:30',
            'regex:/^[A-Za-z0-9._-]+$/',
            function (string $attribute, mixed $value, \Closure $fail) use ($profile): void {
                $normalized = Str::lower((string) $value);
                $reserved = ['admin', 'api', 'help', 'login', 'logout', 'register', 'settings', 'support', 'user', 'users'];

                if (app(PublicHandleService::class)->isReservedAutomaticNamespace((string) $value)) {
                    $fail(__('community_profile.settings.handle_reserved_prefix'));

                    return;
                }

                if (in_array($normalized, $reserved, true)) {
                    $fail(__('community_profile.settings.handle_unavailable'));

                    return;
                }

                $exists = DB::table('user_profiles')
                    ->where('id', '!=', $profile->id)
                    ->where(function ($query) use ($normalized) {
                        $query->whereRaw('LOWER(public_handle) = ?', [$normalized])
                            ->orWhereRaw('LOWER(public_alias) = ?', [$normalized]);
                    })
                    ->exists();

                if ($exists) {
                    $fail(__('community_profile.settings.handle_unavailable'));
                }
            },
        ];
    }

    private function deleteUnusedProfilePhoto(int $photoId, int $userId): void
    {
        app(\App\Services\PhotoDeletionService::class)->purgeUnusedProfilePhoto($photoId, $userId);
    }

    private function profileRecord(): UserProfile
    {
        $user = Auth::user();

        DB::table('user_settings')->insertOrIgnore([
            'user_id' => $user->id,
            'show_real_name' => false,
            'show_reviews_in_profile' => true,
            'show_photos_in_profile' => true,
            'show_join_date' => true,
            'show_activity_counts' => true,
            'allow_email_notifications' => true,
            'profile_photo_visibility' => 'public',
            'bio_visibility' => 'registered',
            'hometown_visibility' => 'registered',
            'age_visibility' => 'private',
            'gender_visibility' => 'private',
            'vehicle_visibility' => 'registered',
            'social_links_visibility' => 'registered',
            'show_gamification' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return UserProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['public_handle' => app(PublicHandleService::class)->automaticForUserId((int) $user->id)],
        );
    }

    private function visibilityValues(): array
    {
        return ['public', 'registered', 'private'];
    }
}; ?>

<style>
    .cw-profile-setting-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        align-items: start;
    }

    @media (min-width: 768px) {
        .cw-profile-setting-row {
            grid-template-columns: minmax(0, 2fr) minmax(220px, 1fr);
        }
    }
</style>

<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('community_profile.settings.title')" :subheading="__('community_profile.settings.subtitle')" :wide="true">
        <div class="my-6 w-full space-y-8">
<section class="space-y-4">
                <div>
                    <flux:heading size="lg">{{ __('community_profile.settings.identity') }}</flux:heading>
                    <flux:text class="mt-1">{{ __('community_profile.settings.handle_current') }}: <strong>{{ $publicHandle }}</strong></flux:text>
                    <flux:text class="mt-1 text-xs">{{ __('community_profile.camperwolf_id') }}: <strong>{{ $automaticHandle }}</strong></flux:text>
                </div>

                @if (! $handleFinalized)
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <flux:text>{{ __('community_profile.settings.handle_dummy_note') }}</flux:text>

                        <div class="mt-4 space-y-3">
                            <flux:input wire:model.live.debounce.300ms="newHandle" :label="__('community_profile.settings.handle_choose')" maxlength="30" />
                            <flux:text class="text-xs">{{ __('community_profile.settings.handle_help') }}</flux:text>
                            <flux:button type="button" wire:click="checkHandle" variant="primary">
                                {{ __('community_profile.settings.handle_check') }}
                            </flux:button>
                        </div>
                    </div>
                @endif

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('users.profile', $publicHandle) }}" class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800">
                        {{ __('community_profile.public_title') }}
                    </a>
                </div>
            </section>

            @if ($handleCheckOpen)
                <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50 px-4" role="dialog" aria-modal="true">
                    <div class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                        @if ($handleCheckAvailable)
                            <flux:heading size="lg">{{ __('community_profile.settings.handle_available_title') }}</flux:heading>
                            <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $handleCheckMessage }}</p>
                            <p class="mt-3 text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ __('community_profile.settings.handle_submit_warning', ['name' => $newHandle]) }}
                            </p>
                            <div class="mt-6 flex justify-end gap-2">
                                <flux:button type="button" wire:click="closeHandleCheck" variant="ghost">
                                    {{ __('community_profile.settings.close') }}
                                </flux:button>
                                <flux:button type="button" wire:click="finalizeHandle" variant="primary">
                                    {{ __('community_profile.settings.handle_submit') }}
                                </flux:button>
                            </div>
                        @else
                            <flux:heading size="lg">{{ __('community_profile.settings.handle_unavailable_title') }}</flux:heading>
                            <p class="mt-3 text-sm text-zinc-700 dark:text-zinc-300">{{ $handleCheckMessage }}</p>
                            <div class="mt-6 flex justify-end">
                                <flux:button type="button" wire:click="closeHandleCheck" variant="primary">
                                    {{ __('community_profile.settings.close_retry') }}
                                </flux:button>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <flux:separator />

            <section class="space-y-4">
                <flux:heading size="lg">{{ __('community_profile.settings.profile_photo') }}</flux:heading>

                <div class="cw-profile-setting-row">
                    <div class="space-y-3">
                        @if ($currentProfilePhotoUrl)
                            <div class="relative inline-block">
                                <img src="{{ $currentProfilePhotoUrl }}" alt="{{ $publicHandle }}" class="shrink-0 rounded-full object-cover ring-1 ring-zinc-200 dark:ring-zinc-700" style="width:96px;height:96px;max-width:96px;max-height:96px;">
                                @if ($showGamification)
                                    <span class="absolute bottom-1 right-1 inline-flex min-w-7 items-center justify-center rounded-full border-2 border-white bg-zinc-900 px-1.5 py-1 text-xs font-bold text-white dark:border-zinc-900 dark:bg-white dark:text-zinc-950">
                                        {{ $gamificationLevel }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        <flux:input wire:model="profilePhoto" type="file" accept="image/jpeg,image/png,image/webp" :label="__('community_profile.settings.profile_photo')" />
                        <flux:text class="text-xs">{{ __('community_profile.settings.profile_photo_help') }}</flux:text>

                        <x-photo-upload-rules variant="profile" />

                        <div class="flex flex-wrap gap-2">
                            <flux:button type="button" wire:click="saveProfilePhoto" variant="primary">{{ __('community_profile.settings.upload_photo') }}</flux:button>
                            @if ($currentProfilePhotoUrl)
                                <flux:button type="button" wire:click="removeProfilePhoto" variant="ghost">{{ __('community_profile.settings.remove_photo') }}</flux:button>
                            @endif
                        </div>
                    </div>

                    <div><flux:select wire:model="profilePhotoVisibility" :label="__('community_profile.settings.visibility')">
                        @foreach ($visibilityValues as $visibility)
                            <flux:select.option value="{{ $visibility }}">{{ __('community_profile.visibility.'.$visibility) }}</flux:select.option>
                        @endforeach
                    </flux:select></div>
                </div>
            </section>

            <flux:separator />

            <form wire:submit="saveProfile" class="space-y-6">
                <section class="space-y-6">
                    <flux:heading size="lg">{{ __('community_profile.settings.personal') }}</flux:heading>

                    <div class="cw-profile-setting-row">
                        <div class="md:col-span-2">
                            <flux:textarea wire:model="bio" :label="__('community_profile.settings.bio')" rows="4" maxlength="500" />
                            <flux:text class="mt-1 text-xs">{{ __('community_profile.settings.bio_help') }}</flux:text>
                        </div>
                        <div><flux:select wire:model="bioVisibility" :label="__('community_profile.settings.visibility')">
                            @foreach ($visibilityValues as $visibility)
                                <flux:select.option value="{{ $visibility }}">{{ __('community_profile.visibility.'.$visibility) }}</flux:select.option>
                            @endforeach
                        </flux:select></div>
                    </div>

                    <div class="cw-profile-setting-row">
                        <div class="md:col-span-2">
                            <div
                                x-data="{
                                    query: @js($hometownCity ? trim($hometownCity.', '.$hometownCountryCode) : ''),
                                    results: [],
                                    status: '',
                                    timer: null,
                                    async search() {
                                        const q = this.query.trim();
                                        if (q.length < 3) { this.results = []; this.status = ''; return; }
                                        this.status = @js(__('community_profile.settings.hometown_searching'));
                                        try {
                                            const params = new URLSearchParams({ q, limit: '7', lang: @js(app()->getLocale()) });
                                            const response = await fetch('https://photon.komoot.io/api/?' + params.toString(), { headers: { Accept: 'application/json' } });
                                            if (! response.ok) throw new Error('HTTP ' + response.status);
                                            const data = await response.json();
                                            this.results = (data.features || []).filter((feature) => {
                                                const p = feature.properties || {};
                                                return ['city', 'town', 'village', 'hamlet', 'locality'].includes(p.type) || p.city || p.locality;
                                            });
                                            this.status = this.results.length ? '' : @js(__('community_profile.settings.hometown_none'));
                                        } catch (e) {
                                            this.results = [];
                                            this.status = @js(__('community_profile.settings.hometown_none'));
                                        }
                                    },
                                    schedule() {
                                        clearTimeout(this.timer);
                                        this.timer = setTimeout(() => this.search(), 350);
                                    },
                                    label(feature) {
                                        const p = feature.properties || {};
                                        const city = p.city || p.locality || p.name || p.county || '';
                                        return [city, p.state, p.country].filter(Boolean).filter((v, i, a) => a.indexOf(v) === i).join(', ');
                                    },
                                    choose(feature) {
                                        const p = feature.properties || {};
                                        const coords = feature.geometry?.coordinates || [];
                                        const city = p.city || p.locality || p.name || '';
                                        $wire.set('hometownCity', city);
                                        $wire.set('hometownCountryCode', (p.countrycode || '').toUpperCase());
                                        $wire.set('hometownLongitude', String(coords[0] ?? ''));
                                        $wire.set('hometownLatitude', String(coords[1] ?? ''));
                                        $wire.set('hometownSourceId', p.osm_id ? String((p.osm_type || 'osm') + ':' + p.osm_id) : '');
                                        this.query = this.label(feature);
                                        this.results = [];
                                        this.status = '';
                                    }
                                }"
                                class="relative"
                            >
                                <label class="block text-sm font-medium">{{ __('community_profile.settings.hometown') }}</label>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('community_profile.settings.hometown_help') }}</p>
                                <input x-model="query" @input="schedule" @keydown.enter.prevent="search" type="search" autocomplete="off" placeholder="{{ __('community_profile.settings.hometown_search') }}" class="mt-2 w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                <div x-show="results.length" class="absolute z-50 mt-1 max-h-64 w-full overflow-y-auto rounded-lg border border-zinc-300 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                                    <template x-for="(feature, index) in results" :key="index">
                                        <button type="button" @click="choose(feature)" class="block w-full border-b border-zinc-200 px-3 py-2 text-left text-sm last:border-b-0 hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-800" x-text="label(feature)"></button>
                                    </template>
                                </div>
                                <p x-text="status" class="mt-1 min-h-5 text-xs text-zinc-500 dark:text-zinc-400"></p>
                            </div>

                            @if ($hometownCity)
                                <div class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                                    <span>{{ __('community_profile.settings.hometown_selected') }}: <strong>{{ $hometownCity }}@if($hometownCountryCode), {{ $hometownCountryCode }}@endif</strong></span>
                                    <flux:button type="button" wire:click="clearHometown" size="sm" variant="ghost">{{ __('community_profile.settings.hometown_clear') }}</flux:button>
                                </div>
                            @endif
                        </div>

                        <div><flux:select wire:model="hometownVisibility" :label="__('community_profile.settings.visibility')">
                            @foreach ($visibilityValues as $visibility)
                                <flux:select.option value="{{ $visibility }}">{{ __('community_profile.visibility.'.$visibility) }}</flux:select.option>
                            @endforeach
                        </flux:select></div>
                    </div>

                    <div class="cw-profile-setting-row">
                        <div class="md:col-span-2">
                            <flux:input wire:model="birthDate" type="date" :label="__('community_profile.settings.birth_date')" />
                            <flux:text class="mt-1 text-xs">{{ __('community_profile.settings.birth_date_help') }}</flux:text>
                        </div>
                        <div><flux:select wire:model="ageVisibility" :label="__('community_profile.settings.age_visibility')">
                            @foreach ($visibilityValues as $visibility)
                                <flux:select.option value="{{ $visibility }}">{{ __('community_profile.visibility.'.$visibility) }}</flux:select.option>
                            @endforeach
                        </flux:select></div>
                    </div>

                    <div class="cw-profile-setting-row">
                        <div class="space-y-3">
                            <flux:select wire:model.live="gender" :label="__('community_profile.settings.gender')">
                                @foreach (['male', 'female', 'nonbinary_other', 'none'] as $value)
                                    <flux:select.option value="{{ $value }}">{{ __('community_profile.gender_values.'.$value) }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            @if ($gender === 'nonbinary_other')
                                <div>
                                    <flux:input wire:model="genderCustom" :label="__('community_profile.settings.gender_custom')" maxlength="80" />
                                    <flux:text class="mt-1 text-xs">{{ __('community_profile.settings.gender_custom_help') }}</flux:text>
                                </div>
                            @endif
                        </div>

                        <div><flux:select wire:model="genderVisibility" :label="__('community_profile.settings.visibility')">
                            @foreach ($visibilityValues as $visibility)
                                <flux:select.option value="{{ $visibility }}">{{ __('community_profile.visibility.'.$visibility) }}</flux:select.option>
                            @endforeach
                        </flux:select></div>
                    </div>

                    <div class="cw-profile-setting-row">
                        <div class="space-y-3">
                            <flux:select wire:model="vehicleType" :label="__('community_profile.settings.vehicle_type')">
                                <flux:select.option value="">—</flux:select.option>
                                @foreach ($vehicleOptions as $value => $label)
                                    <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <div>
                                <flux:input wire:model="vehicleDetails" :label="__('community_profile.settings.vehicle_details')" maxlength="160" />
                                <flux:text class="mt-1 text-xs">{{ __('community_profile.settings.vehicle_details_help') }}</flux:text>
                            </div>
                        </div>

                        <div><flux:select wire:model="vehicleVisibility" :label="__('community_profile.settings.visibility')">
                            @foreach ($visibilityValues as $visibility)
                                <flux:select.option value="{{ $visibility }}">{{ __('community_profile.visibility.'.$visibility) }}</flux:select.option>
                            @endforeach
                        </flux:select></div>
                    </div>
                </section>

                <flux:separator />

                <section class="space-y-3">
                    <div class="flex items-start justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <div class="font-medium">{{ __('community_profile.settings.join_date_title') }}</div>
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('community_profile.settings.join_date_help') }}
                            </div>
                        </div>
                        <input wire:model.live="showJoinDate" type="checkbox" class="mt-1 size-5 shrink-0 rounded border-zinc-300">
                    </div>

                    <div class="flex items-start justify-between gap-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <div class="font-medium">{{ __('community_profile.settings.gamification_title') }}</div>
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('community_profile.settings.gamification_help') }}
                            </div>
                            <a href="{{ route('help.show', 'level-und-xp') }}" class="mt-2 inline-block text-sm font-medium underline underline-offset-2">
                                {{ __('community_profile.settings.gamification_what_is_this') }}
                            </a>
                        </div>
                        <input wire:model.live="showGamification" type="checkbox" class="mt-1 size-5 shrink-0 rounded border-zinc-300">
                    </div>

                    @if ($showGamification)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                            <flux:select wire:model="selectedBadgeId" :label="__('community_profile.settings.selected_title')">
                                <flux:select.option value="">{{ __('community_profile.settings.no_title') }}</flux:select.option>
                                @foreach ($titleOptions as $badgeId => $label)
                                    <flux:select.option value="{{ $badgeId }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:text class="mt-2 text-xs">
                                {{ __('community_profile.settings.selected_title_help') }}
                            </flux:text>
                        </div>
                    @endif
                </section>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">{{ __('community_profile.settings.save') }}</flux:button>
                </div>
            </form>
        </div>
    </x-pages::settings.layout>
</section>
