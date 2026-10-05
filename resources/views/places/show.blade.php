@php
    $__profileRenderStarted = ($profileEnabled ?? false) ? microtime(true) : null;
    $__profileSlotStarted = $__profileRenderStarted;
    $__profileSectionLast = $__profileRenderStarted;
    $__profileSections = [];
@endphp
@if ($profileEnabled ?? false)
    <div class="mx-auto mt-4 w-full max-w-7xl border border-amber-300 bg-amber-50 px-4 py-3 text-xs text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
        <div class="mb-2 font-semibold">Place Profile Performance</div>
        <div class="grid gap-x-6 gap-y-1 sm:grid-cols-2 xl:grid-cols-3">
            @foreach ($profile as $label => $milliseconds)
                <div class="flex justify-between gap-3"><span>{{ $label }}</span><strong>{{ number_format($milliseconds, 2, ',', '.') }} ms</strong></div>
            @endforeach
        </div>
    </div>
@endif

@push('scripts')
<script>
    (() => {
        const initEmailReveal = () => {
            document.querySelectorAll('[data-email-reveal]').forEach((container) => {
                if (container.dataset.bound === '1') return;
                container.dataset.bound = '1';

                const button = container.querySelector('[data-email-reveal-button]');
                const status = container.querySelector('[data-email-reveal-status]');
                if (!button) return;

                button.addEventListener('click', async () => {
                    button.disabled = true;
                    if (status) {
                        status.textContent = @js(__('place_profile.email_loading'));
                        status.classList.remove('hidden');
                    }

                    try {
                        const response = await fetch(container.dataset.url, {
                            headers: { Accept: 'application/json' },
                            credentials: 'same-origin',
                        });

                        if (!response.ok) throw new Error('Email reveal failed');

                        const data = await response.json();
                        if (!data.email) throw new Error('Email missing');

                        const link = document.createElement('a');
                        link.href = 'mailto:' + data.email;
                        link.textContent = data.email;
                        link.className = 'block break-all text-sm font-medium hover:underline';

                        container.replaceChildren(link);
                    } catch (_) {
                        button.disabled = false;
                        if (status) {
                            status.textContent = @js(__('place_profile.email_load_failed'));
                            status.classList.remove('hidden');
                        }
                    }
                });
            });
        };

        document.addEventListener('DOMContentLoaded', initEmailReveal, { once: true });
        document.addEventListener('livewire:navigated', initEmailReveal);
    })();
</script>
@endpush

@push('styles')
<style>
        #profile-map .leaflet-control-attribution { font-size: 10px; }
        .dark #profile-map .leaflet-layer,
        .dark #profile-map .leaflet-control-zoom-in,
        .dark #profile-map .leaflet-control-zoom-out,
        .dark #profile-map .leaflet-control-attribution { filter: brightness(.82) contrast(1.15); }

        .cw-feature-positive {
            border-color: rgb(16 185 129);
            background: rgb(209 250 229);
            color: rgb(6 78 59);
        }
        .cw-feature-positive:hover {
            background: rgb(167 243 208);
        }
        .dark .cw-feature-positive {
            border-color: rgb(5 150 105);
            background: rgba(6, 78, 59, .38);
            color: rgb(167 243 208);
        }
        .dark .cw-feature-positive:hover {
            background: rgba(6, 78, 59, .52);
        }

        .cw-access-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(340px, .9fr);
        }

        .cw-access-map-shell {
            min-height: 320px;
            border-left: 1px solid rgb(228 228 231);
        }

        .dark .cw-access-map-shell {
            border-left-color: rgb(39 39 42);
        }

        #profile-map {
            width: 100%;
            height: 100%;
            min-height: 320px;
        }

        @media (max-width: 1023px) {
            .cw-access-grid {
                grid-template-columns: 1fr;
            }

            .cw-access-map-shell {
                border-left: 0;
                border-top: 1px solid rgb(228 228 231);
            }

            .dark .cw-access-map-shell {
                border-top-color: rgb(39 39 42);
            }
        }

        [data-feature-category] {
            transition: background-color 160ms ease, box-shadow 160ms ease;
        }

        [data-feature-category][data-editing="1"] {
            border-radius: .75rem;
            background: rgb(239 246 255 / .72);
            box-shadow: 0 0 0 2px rgb(147 197 253 / .85);
        }

        .dark [data-feature-category][data-editing="1"] {
            background: rgb(23 37 84 / .28);
            box-shadow: 0 0 0 2px rgb(59 130 246 / .55);
        }

        [data-feature-category][data-editing="1"] [data-category-edit-toggle] {
            background: rgb(219 234 254);
            color: rgb(29 78 216);
        }

        .dark [data-feature-category][data-editing="1"] [data-category-edit-toggle] {
            background: rgb(30 58 138 / .6);
            color: rgb(191 219 254);
        }

        @media (max-width: 767px) {
            [data-profile-feature-group][data-mobile-expanded="0"] > form {
                display: none;
            }

            [data-feature-group-toggle] {
                width: 100%;
                min-height: 2.75rem;
            }

            [data-profile-feature-group][data-mobile-expanded="0"] > div > [data-category-edit-toggle] {
                display: none !important;
            }

            [data-profile-feature-group][data-mobile-expanded="1"] > div > [data-category-edit-toggle] {
                display: inline-flex !important;
                margin-top: 1.65rem;
            }

            .cw-profile-edit-action {
                width: 2.5rem !important;
                height: 2.5rem !important;
            }

            .cw-profile-edit-action svg {
                width: 1.2rem !important;
                height: 1.2rem !important;
            }

            [data-feature-toggle] {
                width: 1.75rem !important;
                height: 1.75rem !important;
            }

            [data-feature-toggle] svg {
                width: 1rem !important;
                height: 1rem !important;
            }
        }
    </style>
@endpush

@push('scripts')
<script>
(() => {
    const bindMoreFeatureGroups = () => {
        document.querySelectorAll('[data-more-feature-groups]').forEach((button) => {
            if (button.dataset.bound) return;
            button.dataset.bound = '1';

            button.addEventListener('click', () => {
                const groups = [...document.querySelectorAll('[data-extra-feature-group]')];
                const shouldShow = groups.some((group) => group.classList.contains('hidden'));
                groups.forEach((group) => group.classList.toggle('hidden', !shouldShow));

                const label = button.querySelector('span');
                if (label) {
                    label.textContent = shouldShow ? button.dataset.hideLabel : button.dataset.showLabel;
                }
            });
        });
    };

    document.addEventListener('DOMContentLoaded', bindMoreFeatureGroups, { once: true });
    document.addEventListener('livewire:navigated', bindMoreFeatureGroups);
})();
</script>
@endpush

@push('scripts')
<script>
(() => {
    const bindPhotoLightbox = () => {
        const dialog = document.querySelector('[data-photo-dialog]');
        if (!dialog || dialog.dataset.bound) return;
        dialog.dataset.bound = '1';

        const image = dialog.querySelector('[data-photo-dialog-image]');
        const caption = dialog.querySelector('[data-photo-dialog-caption]');
        const close = () => dialog.close();

        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-photo-lightbox]');
            if (!trigger) return;
            event.preventDefault();
            image.src = trigger.dataset.photoSrc;
            image.alt = trigger.dataset.photoAlt || @js(__('places.photo_alt'));
            caption.textContent = trigger.dataset.photoCaption || '';
            dialog.showModal();
        });

        dialog.querySelector('[data-photo-dialog-close]')?.addEventListener('click', close);
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog || (!event.target.closest('[data-photo-dialog-image]') && !event.target.closest('[data-photo-dialog-close]'))) {
                close();
            }
        });
        dialog.addEventListener('close', () => {
            image.removeAttribute('src');
            caption.textContent = '';
        });
    };

    document.addEventListener('DOMContentLoaded', bindPhotoLightbox, { once: true });
    document.addEventListener('livewire:navigated', bindPhotoLightbox);
})();
</script>
@endpush

@push('scripts')
<script>
        (() => {
            const initProfileMap = () => {
                const element = document.getElementById('profile-map');
                if (!element || element.dataset.initialized || typeof L === 'undefined') return;

                element.dataset.initialized = '1';
                const position = [{{ (float) $place->latitude }}, {{ (float) $place->longitude }}];
                const map = L.map(element, { zoomControl: true }).setView(position, 14);

                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors'
                }).addTo(map);

                L.marker(position).addTo(map).bindPopup(@json($place->name)).openPopup();
            };

            const initFeatureEditors = () => {
                const categoryForms = [...document.querySelectorAll('[data-feature-category-form]')];
                let activeCategory = null;
                let submitting = false;

                const toneClasses = {
                    positive: ['cw-feature-positive'],
                    negative: ['border-red-500', 'bg-red-100', 'text-red-950', 'hover:bg-red-200', 'dark:border-red-600', 'dark:bg-red-950/35', 'dark:text-red-300', 'dark:hover:bg-red-950/50'],
                    unknown: ['border-zinc-300', 'bg-zinc-100', 'text-zinc-500', 'hover:bg-zinc-200', 'dark:border-zinc-700', 'dark:bg-zinc-900', 'dark:text-zinc-500', 'dark:hover:bg-zinc-800'],
                };
                const allToneClasses = [...toneClasses.positive, ...toneClasses.negative, ...toneClasses.unknown];

                const serializeFeature = (shell) => {
                    const values = {};
                    shell.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                        values[field.name] = field.type === 'checkbox' ? field.checked : field.value;
                    });
                    return JSON.stringify(values);
                };

                const snapshotForm = (form) => {
                    form.querySelectorAll('[data-feature-shell]').forEach((shell) => {
                        shell.dataset.initialSnapshot = serializeFeature(shell);
                    });
                };

                const markTone = (shell, status) => {
                    shell.classList.remove(...allToneClasses);
                    const tone = ['unavailable', 'no'].includes(status)
                        ? 'negative'
                        : (status === 'unknown' ? 'unknown' : 'positive');
                    shell.classList.add(...toneClasses[tone]);
                };

                const refreshEditor = (editor) => {
                    const status = editor.querySelector('[data-feature-status]');
                    const current = status?.value ?? 'unknown';
                    const shell = editor.closest('[data-feature-shell]');

                    editor.querySelectorAll('[data-status-choice]').forEach((button) => {
                        const selected = button.dataset.statusChoice === current;
                        button.classList.toggle('border-zinc-900', selected);
                        button.classList.toggle('bg-zinc-900', selected);
                        button.classList.toggle('text-white', selected);
                        button.classList.toggle('dark:border-white', selected);
                        button.classList.toggle('dark:bg-white', selected);
                        button.classList.toggle('dark:text-zinc-950', selected);

                        button.classList.toggle('border-zinc-300', !selected);
                        button.classList.toggle('bg-white', !selected);
                        button.classList.toggle('text-zinc-700', !selected);
                        button.classList.toggle('hover:bg-zinc-100', !selected);
                        button.classList.toggle('dark:border-zinc-700', !selected);
                        button.classList.toggle('dark:bg-zinc-950', !selected);
                        button.classList.toggle('dark:text-zinc-200', !selected);
                        button.classList.toggle('dark:hover:bg-zinc-800', !selected);
                    });

                    editor.querySelectorAll('[data-show-for]').forEach((row) => {
                        const allowed = (row.dataset.showFor || '').split(',').filter(Boolean);
                        row.classList.toggle('hidden', !allowed.includes(current));
                    });

                    editor.querySelectorAll('[data-pricing-status]').forEach((pricingStatus) => {
                        const pricingBox = pricingStatus.closest('[data-pricing]');
                        pricingBox?.querySelector('[data-paid-fields]')?.classList.toggle('hidden', pricingStatus.value !== 'paid');
                    });

                    if (shell) {
                        const original = shell.dataset.originalStatus ?? 'unknown';
                        const display = shell.dataset.displayStatus ?? original;
                        const toneStatus = current === original ? display : current;
                        markTone(shell, toneStatus);
                    }
                };

                const dirtyShells = (form) => [...form.querySelectorAll('[data-feature-shell]')]
                    .filter((shell) => shell.dataset.dirty === '1');

                const saveDraft = (form) => {
                    const dirty = dirtyShells(form);
                    if (dirty.length === 0) {
                        try { sessionStorage.removeItem(form.dataset.storageKey); } catch (_) {}
                        return;
                    }

                    const values = {};
                    dirty.forEach((shell) => {
                        shell.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                            values[field.name] = field.type === 'checkbox' ? field.checked : field.value;
                        });
                    });

                    try {
                        sessionStorage.setItem(form.dataset.storageKey, JSON.stringify(values));
                    } catch (_) {}
                };

                const refreshDirtyState = (form) => {
                    form.querySelectorAll('[data-feature-shell]').forEach((shell) => {
                        const dirty = serializeFeature(shell) !== shell.dataset.initialSnapshot;
                        shell.dataset.dirty = dirty ? '1' : '0';
                        shell.querySelector('[data-feature-dirty-indicator]')?.classList.toggle('hidden', !dirty);
                    });

                    const count = dirtyShells(form).length;
                    const actions = form.querySelector('[data-category-actions]');
                    const submit = form.querySelector('[data-category-submit]');
                    const countNode = form.querySelector('[data-category-change-count]');

                    if (countNode) {
                        countNode.textContent = count === 1
                            ? @js(trans_choice('feature_workflow.category_changes_count', 1, ['count' => 1]))
                            : @js(trans_choice('feature_workflow.category_changes_count', 2, ['count' => '__COUNT__'])).replace('__COUNT__', count);
                    }
                    if (submit) submit.disabled = count === 0;
                    saveDraft(form);

                    if (actions && form.closest('[data-feature-category]')?.dataset.editing === '1') {
                        actions.classList.remove('hidden');
                        actions.classList.add('flex');
                    }
                };

                const closeEditors = (form) => {
                    form.querySelectorAll('[data-feature-editor]').forEach((editor) => editor.classList.add('hidden'));
                    form.querySelectorAll('[data-feature-shell]').forEach((shell) => {
                        shell.classList.remove('basis-full', 'w-full');
                    });
                };

                const leaveCategory = (category, discard = false) => {
                    const form = category?.querySelector('[data-feature-category-form]');
                    if (!form) return true;

                    if (!discard && dirtyShells(form).length > 0) {
                        return false;
                    }

                    category.dataset.editing = '0';
                    category.querySelector('[data-category-edit-toggle]')?.setAttribute('aria-pressed', 'false');
                    const actions = form.querySelector('[data-category-actions]');
                    actions?.classList.add('hidden');
                    actions?.classList.remove('flex');
                    closeEditors(form);
                    if (activeCategory === category) activeCategory = null;
                    return true;
                };

                const enterCategory = (category) => {
                    if (activeCategory && activeCategory !== category) {
                        const activeForm = activeCategory.querySelector('[data-feature-category-form]');
                        if (activeForm && dirtyShells(activeForm).length > 0) {
                            if (!window.confirm(@js(__('feature_workflow.discard_confirm')))) return;
                            try { sessionStorage.removeItem(activeForm.dataset.storageKey); } catch (_) {}
                            window.location.reload();
                            return;
                        }
                        leaveCategory(activeCategory, true);
                    }

                    category.dataset.editing = '1';
                    category.dataset.mobileExpanded = '1';
                    category.querySelector('[data-category-edit-toggle]')?.setAttribute('aria-pressed', 'true');
                    const groupToggle = category.querySelector('[data-feature-group-toggle]');
                    groupToggle?.setAttribute('aria-expanded', 'true');
                    const chevron = groupToggle?.querySelector('[data-feature-group-chevron]');
                    if (chevron) chevron.textContent = '▲';

                    const form = category.querySelector('[data-feature-category-form]');
                    const actions = form?.querySelector('[data-category-actions]');
                    actions?.classList.remove('hidden');
                    actions?.classList.add('flex');
                    activeCategory = category;
                };

                const restoreDraft = (form) => {
                    let draft = null;
                    try {
                        draft = JSON.parse(sessionStorage.getItem(form.dataset.storageKey) || 'null');
                    } catch (_) {}

                    if (!draft || typeof draft !== 'object') return false;

                    Object.entries(draft).forEach(([name, value]) => {
                        const field = [...form.elements].find((element) => element.name === name);
                        if (!field) return;
                        if (field.type === 'checkbox') field.checked = Boolean(value);
                        else field.value = value;
                    });

                    form.querySelectorAll('[data-feature-editor]').forEach(refreshEditor);
                    return true;
                };

                categoryForms.forEach((form) => {
                    if (form.dataset.bound) return;
                    form.dataset.bound = '1';

                    snapshotForm(form);
                    const restored = restoreDraft(form);
                    if (restored) {
                        form.querySelectorAll('[data-feature-shell]').forEach((shell) => {
                            const dirty = serializeFeature(shell) !== shell.dataset.initialSnapshot;
                            shell.dataset.dirty = dirty ? '1' : '0';
                            shell.querySelector('[data-feature-dirty-indicator]')?.classList.toggle('hidden', !dirty);
                        });
                        refreshDirtyState(form);
                        if (dirtyShells(form).length > 0) enterCategory(form.closest('[data-feature-category]'));
                    }

                    form.querySelectorAll('[data-feature-editor]').forEach((editor) => {
                        editor.querySelectorAll('[data-status-choice]').forEach((button) => {
                            button.addEventListener('click', () => {
                                const status = editor.querySelector('[data-feature-status]');
                                if (!status) return;
                                status.value = button.dataset.statusChoice;
                                refreshEditor(editor);
                                refreshDirtyState(form);
                            });
                        });

                        editor.querySelectorAll('input, select, textarea').forEach((field) => {
                            if (field.matches('[data-feature-status]')) return;
                            field.addEventListener('input', () => refreshDirtyState(form));
                            field.addEventListener('change', () => {
                                refreshEditor(editor);
                                refreshDirtyState(form);
                            });
                        });

                        refreshEditor(editor);
                    });

                    form.querySelectorAll('[data-feature-open]').forEach((button) => {
                        button.addEventListener('click', () => {
                            const category = form.closest('[data-feature-category]');
                            if (category?.dataset.editing !== '1') return;

                            const target = document.getElementById(button.dataset.featureOpen);
                            if (!target) return;
                            const shell = target.closest('[data-feature-shell]');
                            const opening = target.classList.contains('hidden');

                            closeEditors(form);
                            if (opening) {
                                target.classList.remove('hidden');
                                shell?.classList.add('basis-full', 'w-full');
                                target.querySelector('button, select, input, textarea')?.focus();
                            }
                        });
                    });

                    form.closest('[data-feature-category]')?.querySelector('[data-category-edit-toggle]')?.addEventListener('click', () => {
                        const category = form.closest('[data-feature-category]');
                        if (category?.dataset.editing === '1') {
                            if (dirtyShells(form).length > 0 && !window.confirm(@js(__('feature_workflow.discard_confirm')))) return;
                            try { sessionStorage.removeItem(form.dataset.storageKey); } catch (_) {}
                            window.location.reload();
                            return;
                        }
                        enterCategory(category);
                    });

                    form.querySelector('[data-category-discard]')?.addEventListener('click', () => {
                        if (dirtyShells(form).length > 0 && !window.confirm(@js(__('feature_workflow.discard_confirm')))) return;
                        try { sessionStorage.removeItem(form.dataset.storageKey); } catch (_) {}
                        window.location.reload();
                    });

                    form.addEventListener('submit', () => {
                        submitting = true;
                        dirtyShells(form).forEach((shell) => {
                            shell.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                                field.disabled = false;
                            });
                        });
                        form.querySelectorAll('[data-feature-shell]').forEach((shell) => {
                            if (shell.dataset.dirty === '1') return;
                            shell.querySelectorAll('input[name], select[name], textarea[name]').forEach((field) => {
                                field.disabled = true;
                            });
                        });
                        try { sessionStorage.removeItem(form.dataset.storageKey); } catch (_) {}
                    });
                });

                document.querySelectorAll('[data-feature-info]').forEach((button) => {
                    if (button.dataset.bound) return;
                    button.dataset.bound = '1';
                    const tooltip = button.querySelector('[data-feature-tooltip]');
                    const show = () => {
                        tooltip?.classList.remove('hidden');
                        button.setAttribute('aria-expanded', 'true');
                    };
                    const hide = () => {
                        tooltip?.classList.add('hidden');
                        button.setAttribute('aria-expanded', 'false');
                    };
                    button.addEventListener('mouseenter', show);
                    button.addEventListener('mouseleave', hide);
                    button.addEventListener('focus', show);
                    button.addEventListener('blur', hide);
                    button.addEventListener('click', (event) => {
                        event.stopPropagation();
                        if (tooltip?.classList.contains('hidden')) show();
                        else hide();
                    });
                });

                if (!window.__camperwolfFeatureLeaveWarningBound) {
                    window.__camperwolfFeatureLeaveWarningBound = true;
                    window.addEventListener('beforeunload', (event) => {
                        if (submitting) return;
                        const hasDirty = [...document.querySelectorAll('[data-feature-category-form]')]
                            .some((form) => dirtyShells(form).length > 0);
                        if (!hasDirty) return;
                        event.preventDefault();
                        event.returnValue = @js(__('feature_workflow.leave_warning'));
                    });
                }
            };

            const initMobileFeatureGroups = () => {
                document.querySelectorAll('[data-profile-feature-group]').forEach((group) => {
                    if (!group.dataset.mobileExpanded) group.dataset.mobileExpanded = '0';
                    const toggle = group.querySelector('[data-feature-group-toggle]');
                    if (!toggle || toggle.dataset.bound) return;
                    toggle.dataset.bound = '1';

                    toggle.addEventListener('click', () => {
                        if (window.innerWidth >= 768) return;
                        const expanded = group.dataset.mobileExpanded === '1';
                        group.dataset.mobileExpanded = expanded ? '0' : '1';
                        toggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
                        const chevron = toggle.querySelector('[data-feature-group-chevron]');
                        if (chevron) chevron.textContent = expanded ? '▼' : '▲';
                    });
                });
            };

            const init = () => {
                initProfileMap();
                initFeatureEditors();
                initMobileFeatureGroups();
            };

            document.addEventListener('DOMContentLoaded', init, { once: true });
            document.addEventListener('livewire:navigated', init);
            window.addEventListener('camperwolf:leaflet-ready', init);
        })();
    </script>
@endpush

@php
    $socialTitle = $place->name;
    $socialDescription = filled($translation?->description)
        ? \Illuminate\Support\Str::limit(trim(strip_tags((string) $translation->description)), 170)
        : (filled($place->city)
            ? __('ui.social.place_fallback_description', [
                'type' => $place->place_type_label,
                'location' => $place->city,
            ])
            : __('ui.social.place_fallback_description_no_location', [
                'type' => $place->place_type_label,
            ]));
    $socialImage = $placeThumbnail && empty($placeThumbnail->external_source_id)
        ? route('photos.show', ['uuid' => $placeThumbnail->uuid, 'variant' => 'preview'])
        : asset('images/camperwolf-placeholder.png');
@endphp

<x-layouts::app
    :title="$place->name"
    :social-title="$socialTitle"
    :meta-description="$socialDescription"
    :social-image="$socialImage"
    :canonical-url="route('places.show', $place->slug)"
>
    @php
        $legalLabels = __('ui.browse.legal_status');
        $operatingStatusLabels = __('ui.browse.opening_status');
        $addressLine = trim(implode(' ', array_filter([$place->street, $place->house_number])));
        $locationLine = trim(implode(' ', array_filter([$place->postal_code, $place->city])));
        $workflowService = app(\App\Services\FeatureWorkflowService::class);
        $locale = app()->getLocale();
    @endphp

    <div class="mx-auto w-full max-w-[1500px] px-2 py-4 sm:px-6 sm:py-6">
        <div class="mb-5 flex items-center justify-between gap-4">
            <a href="{{ url()->previous() === url()->current() ? route('dashboard') : url()->previous() }}" class="text-sm font-medium text-zinc-500 hover:text-zinc-950 dark:hover:text-white">
                ← {{ __('place_profile.back_search') }}
            </a>
            <div class="flex items-center gap-2">
                @if ($canFavorite)
                    <form method="POST" action="{{ $isFavorite ? route('favorites.destroy', $place->slug) : route('favorites.store', $place->slug) }}">
                        @csrf
                        @if ($isFavorite)
                            @method('DELETE')
                        @endif
                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border px-3 text-sm font-medium transition {{ $isFavorite ? 'border-red-300 bg-red-50 text-red-700 hover:bg-red-100 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50' : 'border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800' }}">
                            <span class="text-2xl leading-none {{ $isFavorite ? 'text-red-600 dark:text-red-500' : '' }}">{{ $isFavorite ? '♥' : '♡' }}</span>
                            <span>{{ __('place_profile.favorite') }}</span>
                        </button>
                    </form>
                @elseif (! auth()->check())
                    <button
                        type="button"
                        data-auth-feature
                        data-auth-title="{{ __('place_profile.favorite_guest_title') }}"
                        data-auth-message="{{ __('place_profile.favorite_guest_help') }}"
                        class="inline-flex h-10 items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-200 dark:hover:bg-zinc-800"
                    >
                        <span class="text-2xl leading-none">♡</span>
                        <span>{{ __('place_profile.favorite') }}</span>
                    </button>
                @endif
                @if ($canDirectEdit || $canSuggest)
                    <a href="{{ route('places.info-suggest.edit', $place->slug) }}" class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">
                        {{ $canDirectEdit ? __('place_profile.edit') : __('place_profile.suggest_change') }}
                    </a>
                    @if ($canPermanentlyDelete)
                        <details class="relative">
                            <summary class="cursor-pointer list-none rounded-lg border border-red-300 px-3 py-2 text-sm font-medium text-red-700 hover:bg-red-50 dark:border-red-900 dark:text-red-300 dark:hover:bg-red-950/30">
                                {{ __('admin.place_deletion.action') }}
                            </summary>
                            <div class="absolute right-0 z-40 mt-2 w-[26rem] max-w-[90vw] rounded-xl border border-red-200 bg-white p-4 shadow-xl dark:border-red-900 dark:bg-zinc-900">
                                <div class="font-semibold text-red-700 dark:text-red-300">{{ __('admin.place_deletion.title') }}</div>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-300">{{ __('admin.place_deletion.help') }}</p>
                                <form method="POST" action="{{ route('admin.places.delete-permanently', $place->id) }}" class="mt-4 space-y-3">
                                    @csrf
                                    @method('DELETE')
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold">{{ __('admin.place_deletion.reason') }}</span>
                                        <select name="deletion_reason" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                            @foreach (['operator_request', 'wrong_place', 'duplicate_or_false_entry', 'import_false_positive', 'manual_block', 'other'] as $reason)
                                                <option value="{{ $reason }}">{{ __('admin.place_deletion.reasons.'.$reason) }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold">{{ __('admin.place_deletion.note') }}</span>
                                        <textarea name="deletion_note" rows="3" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-semibold">{{ __('admin.place_deletion.confirmation', ['name' => $place->name]) }}</span>
                                        <input name="confirmation" required autocomplete="off" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                                    </label>
                                    <button class="w-full rounded-lg bg-red-700 px-3 py-2 text-sm font-semibold text-white hover:bg-red-800">
                                        {{ __('admin.place_deletion.confirm') }}
                                    </button>
                                </form>
                            </div>
                        </details>
                    @endif
                @elseif (! auth()->check())
                    <button
                        type="button"
                        data-auth-feature
                        data-auth-title="{{ __('place_profile.suggest_change') }}"
                        data-auth-message="{{ __('place_profile.suggest_guest_help') }}"
                        class="rounded-lg bg-zinc-900 px-3 py-2 text-sm font-medium text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                    >
                        {{ __('place_profile.suggest_change') }}
                    </button>
                @endif
            </div>
        </div>


        <div class="space-y-6">
        <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            @if ($placeThumbnail)
                <button
                    type="button"
                    data-photo-lightbox
                    data-photo-src="{{ route('photos.show', ['uuid' => $placeThumbnail->uuid, 'variant' => 'detail']) }}"
                    data-photo-alt="{{ __('place_profile.cover_alt', ['place' => $place->name]) }}"
                    data-photo-caption="{{ $place->name }}"
                    class="group relative block h-64 w-full overflow-hidden bg-zinc-100 text-left sm:h-80 lg:h-96 dark:bg-zinc-800"
                >
                    <img
                        src="{{ route('photos.show', ['uuid' => $placeThumbnail->uuid, 'variant' => 'detail']) }}"
                        alt="{{ __('place_profile.cover_alt', ['place' => $place->name]) }}"
                        class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.01]"
                    >
                    @if ($placeThumbnail->source_license_code)
                        <span class="absolute bottom-3 left-3 max-w-[70%] rounded bg-black/70 px-2 py-1 text-[10px] text-white">
                            {{ __('place_profile.external_photo_credit', [
                                'author' => $placeThumbnail->source_author ?: $placeThumbnail->source_provider,
                                'license' => $placeThumbnail->source_license_code,
                            ]) }}
                        </span>
                    @endif
                    <span class="absolute bottom-3 right-3 rounded-full bg-black/65 px-3 py-1.5 text-xs font-medium text-white">{{ __('place_profile.enlarge') }}</span>
                </button>
            @else
                <div class="grid h-48 place-items-center bg-zinc-100 px-6 text-center text-sm text-zinc-400 sm:h-64 dark:bg-zinc-800">
                    {{ __('place_profile.no_photo') }}
                </div>
            @endif

            <div class="p-3 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between sm:gap-6">
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium text-zinc-500">{{ $place->place_type_label }}</p>
                        <h1 class="mt-1 text-xl font-semibold tracking-tight text-zinc-950 sm:text-3xl dark:text-white">{{ $place->name }}</h1>
                        <p class="mt-2 text-sm text-zinc-500">
                            @if ($addressLine !== '') {{ $addressLine }} · @endif
                            {{ $locationLine !== '' ? $locationLine : __('place_profile.location_missing') }}
                            @if ($place->country_code) · {{ $place->country_code }} @endif
                            @if (filled($place->address_addition)) · {{ $place->address_addition }} @endif
                        </p>

                        <div class="mt-5 max-w-4xl">
                            <div class="flex items-center gap-2">
                                <div class="text-xs font-medium text-zinc-400">
                                    {{ __('place_profile.text_info_count', ['known' => $descriptionInfoCount, 'total' => $descriptionInfoTotal]) }}
                                </div>
                                @if ($canSuggest)
                                    <a href="{{ route('places.info-suggest.edit', $place->slug) }}" class="cw-profile-edit-action inline-flex size-5 shrink-0 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" title="{{ __('place_profile.edit_description') }}" aria-label="{{ __('place_profile.edit_description') }}">
                                        <x-tabler-icon name="pencil" class="size-3.5" />
                                    </a>
                                @endif
                            </div>
                            @if ($translation?->description)
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-zinc-700 dark:text-zinc-300">{{ $translation->description }}</p>
                            @else
                                <p class="mt-2 text-sm italic text-zinc-400">{{ __('place_profile.no_description') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="hidden w-80 shrink-0 lg:block">
                        @include('places._data-score')
                    </div>
                </div>

                <div class="mt-4 lg:hidden">
                    @include('places._data-score')
                </div>

                <div class="mt-3">
                    <details class="group w-full border-t border-zinc-100 pt-2 dark:border-zinc-800">
                        <summary class="ml-auto flex w-fit cursor-pointer list-none items-center gap-1 text-xs text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200">
                            <x-tabler-icon name="history" class="size-3.5" />
                            {{ __('place_profile.history.title') }}
                        </summary>
                        <div class="mt-3 h-52 w-full overflow-y-scroll overflow-x-auto border-t border-zinc-200 dark:border-zinc-700">
                            <table class="w-full min-w-[820px] text-left text-xs">
                                <thead class="sticky top-0 z-10 bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                    <tr>
                                        <th class="px-3 py-2 font-semibold">{{ __('place_profile.history.author') }}</th>
                                        <th class="px-3 py-2 font-semibold">{{ __('place_profile.history.source') }}</th>
                                        <th class="px-3 py-2 font-semibold">{{ __('place_profile.history.date') }}</th>
                                        <th class="px-3 py-2 font-semibold">{{ __('place_profile.history.change') }}</th>
                                        <th class="px-3 py-2 font-semibold">{{ __('place_profile.history.approved') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                                    @forelse ($placeHistory as $history)
                                        <tr class="align-top">
                                            <td class="whitespace-nowrap px-3 py-2 font-medium">
                                                @if ($history->actor_profile_handle)
                                                    <a href="{{ route('users.profile', ['handle' => $history->actor_profile_handle]) }}" class="underline decoration-zinc-300 underline-offset-2 hover:text-zinc-700 dark:decoration-zinc-600 dark:hover:text-zinc-200">{{ $history->actor }}</a>
                                                @else
                                                    {{ $history->actor }}
                                                @endif
                                            </td>
                                            <td class="px-3 py-2">
                                                @if ($history->source_label)
                                                    @if ($history->source_url)
                                                        <a href="{{ $history->source_url }}" target="_blank" rel="noopener noreferrer" class="underline decoration-zinc-300 underline-offset-2 hover:text-zinc-700 dark:decoration-zinc-600 dark:hover:text-zinc-200">
                                                            {{ $history->source_label }}
                                                        </a>
                                                    @else
                                                        {{ $history->source_label }}
                                                    @endif
                                                @else
                                                    <span class="text-zinc-400">-</span>
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2 text-zinc-500">{{ \App\Support\LocalTime::parse($history->submitted_at)->format('d.m.Y H:i') }}</td>
                                            <td class="px-3 py-2">
                                                @if ($history->changes->isNotEmpty())
                                                    <div class="space-y-1">
                                                        @foreach ($history->changes as $change)
                                                            <div>{{ $change }}</div>
                                                        @endforeach
                                                    </div>
                                                @else
                                                    {{ $history->summary }}
                                                @endif
                                            </td>
                                            <td class="whitespace-nowrap px-3 py-2 text-zinc-500">
                                                {{ $history->approved_at ? \App\Support\LocalTime::parse($history->approved_at)->format('d.m.Y H:i') : '—' }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="px-3 py-5 text-center text-zinc-400">{{ __('place_profile.history.empty') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </details>
                </div>
            </div>
        </section>


            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-xl border border-zinc-200 bg-white p-3 sm:p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold">{{ __('place_profile.details') }}</h2>
                            <div class="mt-1 text-xs text-zinc-400">{{ __('place_profile.basic_info_count', ['known' => $basicInfoCount, 'total' => $basicInfoTotal]) }}</div>
                        </div>
                        @if ($canSuggest)
                            <a href="{{ route('places.info-suggest.edit', $place->slug) }}" class="cw-profile-edit-action inline-flex size-7 shrink-0 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" title="{{ __('place_profile.edit_details') }}" aria-label="{{ __('place_profile.edit_details') }}">
                                <x-tabler-icon name="pencil" class="size-3.5" />
                            </a>
                        @endif
                    </div>

                    <dl class="mt-4 divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                        <div class="flex justify-between gap-5 py-2.5 first:pt-0">
                            <dt class="text-zinc-500">{{ __('place_profile.type') }}</dt>
                            <dd class="text-right font-medium">{{ $place->place_type_label }}</dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.operator') }}</dt>
                            <dd class="text-right font-medium {{ filled($details?->operator_name) ? '' : 'italic text-zinc-400' }}">
                                {{ filled($details?->operator_name) ? $details->operator_name : __('place_profile.not_provided') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.pitches') }}</dt>
                            <dd class="text-right font-medium {{ $details?->pitch_count !== null ? '' : 'italic text-zinc-400' }}">
                                {{ $details?->pitch_count !== null ? $details->pitch_count : __('place_profile.not_provided') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.minimum_stay_nights') }}</dt>
                            <dd class="text-right font-medium {{ $details?->minimum_stay_nights !== null ? '' : 'italic text-zinc-400' }}">
                                {{ $details?->minimum_stay_nights !== null ? trans_choice('place_profile.minimum_stay_value', $details->minimum_stay_nights, ['count' => $details->minimum_stay_nights]) : __('place_profile.not_provided') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.pitch_area_min_m2') }}</dt>
                            <dd class="text-right font-medium {{ $details?->pitch_area_min_m2 !== null ? '' : 'italic text-zinc-400' }}">
                                {{ $details?->pitch_area_min_m2 !== null ? rtrim(rtrim(number_format((float) $details->pitch_area_min_m2, 2, ',', '.'), '0'), ',').' m²' : __('place_profile.not_provided') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.operation') }}</dt>
                            <dd class="text-right font-medium">
                                @if ($place->opening_status === 'unclear')
                                    <span class="italic text-zinc-400">{{ __('place_profile.unknown') }}</span>
                                @elseif ($place->opening_status === 'open')
                                    <span class="text-emerald-700 dark:text-emerald-400">{{ __('ui.browse.operating_status.active') }}</span>
                                    @if ($openingClosureHint)
                                        <div class="mt-1 max-w-sm text-xs font-medium text-red-700 dark:text-red-400">
                                            {{ $openingClosureHint['kind'] === 'year_round'
                                                ? __('place_profile.opening_closure_probably_permanent')
                                                : __('place_profile.opening_closure_until', ['date' => $openingClosureHint['until']]) }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-red-700 dark:text-red-400">{{ $operatingStatusLabels[$place->opening_status] ?? str($place->opening_status)->headline() }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.legal_status') }}</dt>
                            <dd class="text-right font-medium {{ $place->legal_status === 'unclear' ? 'italic text-zinc-400' : '' }}">
                                {{ $place->legal_status !== 'unclear' ? ($legalLabels[$place->legal_status] ?? str($place->legal_status)->replace('_', ' ')->headline()) : __('place_profile.not_provided') }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.opening_status') }}</dt>
                            <dd class="text-right font-medium">
                                @if (! $currentOpeningState)
                                    <span class="italic text-zinc-400">{{ __('place_profile.unknown') }}</span>
                                @elseif ($currentOpeningState['state'] === 'open')
                                    <span class="text-emerald-700 dark:text-emerald-400">{{ __('ui.browse.current_opening.open') }}</span>
                                @elseif ($currentOpeningState['state'] === 'closing_soon')
                                    <span class="text-amber-700 dark:text-amber-400">{{ __('ui.browse.current_opening.closing_soon', ['minutes' => $currentOpeningState['minutes_until_close']]) }}</span>
                                @elseif ($currentOpeningState['state'] === 'opening_soon')
                                    <span class="text-amber-700 dark:text-amber-400">{{ __('ui.browse.current_opening.opening_soon', ['minutes' => $currentOpeningState['minutes_until_open']]) }}</span>
                                @else
                                    <span class="text-red-700 dark:text-red-400">{{ __('ui.browse.current_opening.closed') }}</span>
                                @endif
                            </dd>
                        </div>
                        <div class="flex justify-between gap-5 py-2.5">
                            <dt class="text-zinc-500">{{ __('place_profile.website') }}</dt>
                            <dd class="min-w-0 text-right font-medium">
                                @if ($website?->value)
                                    <a href="{{ $website->value }}" target="_blank" rel="noopener" class="break-all hover:underline">{{ $website->value }}</a>
                                @else
                                    <span class="italic text-zinc-400">{{ __('place_profile.not_provided') }}</span>
                                @endif
                            </dd>
                        </div>
                    </dl>

                    @if ($canSuggest && $basicInfoCount < $basicInfoTotal)
                        <a href="{{ route('places.info-suggest.edit', $place->slug) }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-900 dark:hover:text-white">
                            <x-tabler-icon name="pencil" class="size-3.5" />
                            {{ __('place_profile.add_information') }}
                        </a>
                    @endif


                </section>

            </div>

            <section class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="cw-access-grid">
                    <div class="p-3 sm:p-5 lg:p-6">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-lg font-semibold">{{ __('place_profile.access_title') }}</h2>
                            </div>
                            @if ($canSuggest)
                                <a href="{{ route('places.info-suggest.edit', $place->slug) }}" class="cw-profile-edit-action inline-flex size-7 shrink-0 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" title="{{ __('place_profile.edit_access') }}" aria-label="{{ __('place_profile.edit_access') }}">
                                    <x-tabler-icon name="pencil" class="size-3.5" />
                                </a>
                            @endif
                        </div>

                        <div class="mt-4 space-y-5">
                            <div>
                                <h3 class="text-sm font-medium">{{ __('place_profile.address_position') }}</h3>
                                <div class="mt-1 text-sm leading-6 text-zinc-600 dark:text-zinc-300">
                                    @if ($addressLine !== '')
                                        <div>{{ $addressLine }}</div>
                                    @endif
                                    @if ($locationLine !== '' || $place->country_code)
                                        <div>
                                            {{ $locationLine !== '' ? $locationLine : __('place_profile.location_missing') }}
                                            @if ($place->country_code) · {{ $place->country_code }} @endif
                                        </div>
                                    @endif
                                    @if (filled($place->address_addition))
                                        <div>{{ $place->address_addition }}</div>
                                    @endif
                                    <div class="mt-1 text-xs text-zinc-500">
                                        {{ number_format((float) $place->latitude, 5, ',', '.') }},
                                        {{ number_format((float) $place->longitude, 5, ',', '.') }}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium">{{ __('place_profile.external_links') }}</h3>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <a
                                        href="https://www.google.com/maps/dir/?api=1&destination={{ $place->latitude }},{{ $place->longitude }}"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                                    >
                                        <x-tabler-icon name="brand-google-maps" class="size-4" />
                                        {{ __('place_profile.google_route') }}
                                    </a>

                                    <a
                                        href="https://maps.apple.com/?daddr={{ $place->latitude }},{{ $place->longitude }}&dirflg=d"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                                    >
                                        <x-tabler-icon name="map" class="size-4" />
                                        {{ __('place_profile.apple_route') }}
                                    </a>

                                    <a
                                        href="https://www.waze.com/ul?ll={{ $place->latitude }},{{ $place->longitude }}&navigate=yes"
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800"
                                    >
                                        <x-tabler-icon name="route" class="size-4" />
                                        {{ __('place_profile.waze_route') }}
                                    </a>
                                </div>
                                <p class="mt-2 text-xs text-zinc-400">
                                    {{ __('place_profile.map_app_help') }}
                                </p>
                            </div>

                            <div>
                                <h3 class="text-sm font-medium">{{ __('place_profile.directions') }}</h3>
                                @if ($translation?->directions)
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $translation->directions }}</p>
                                @else
                                    <p class="mt-1 text-sm italic text-zinc-400">{{ __('place_profile.no_directions') }}</p>
                                @endif
                            </div>

                            <div>
                                <h3 class="text-sm font-medium">{{ __('place_profile.access') }}</h3>
                                @if ($translation?->access_information)
                                    <p class="mt-1 whitespace-pre-line text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $translation->access_information }}</p>
                                @else
                                    <p class="mt-1 text-sm italic text-zinc-400">{{ __('place_profile.no_access') }}</p>
                                @endif
                            </div>

                            @php
                                $additionalContacts = $contacts->reject(
                                    fn ($contact) => in_array($contact->contact_type, ['website', 'url', 'email'], true)
                                );
                                $hasEmailContact = $contacts->contains(
                                    fn ($contact) => $contact->contact_type === 'email' && filled($contact->value)
                                );
                            @endphp
                            @if ($additionalContacts->isNotEmpty() || $hasEmailContact)
                                <div>
                                    <h3 class="text-sm font-medium">{{ __('place_profile.contact') }}</h3>
                                    <div class="mt-2 grid gap-3 sm:grid-cols-2">
                                        @foreach ($additionalContacts as $contact)
                                            @php
                                                $href = match ($contact->contact_type) {
                                                    'phone', 'telephone' => 'tel:'.$contact->value,
                                                    'website', 'url' => $contact->value,
                                                    default => null,
                                                };
                                            @endphp
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-zinc-400">{{ \Illuminate\Support\Facades\Lang::has('place_profile.contact_types.'.$contact->contact_type) ? __('place_profile.contact_types.'.$contact->contact_type) : str($contact->contact_type)->replace('_', ' ')->headline() }}</div>
                                                @if ($href)
                                                    <a href="{{ $href }}" class="mt-0.5 block break-all text-sm font-medium hover:underline" @if (in_array($contact->contact_type, ['website', 'url'])) target="_blank" rel="noopener" @endif>{{ $contact->value }}</a>
                                                @else
                                                    <div class="mt-0.5 break-all text-sm font-medium">{{ $contact->value }}</div>
                                                @endif
                                            </div>
                                        @endforeach

                                        @if ($hasEmailContact)
                                            <div>
                                                <div class="text-xs uppercase tracking-wide text-zinc-400">{{ __('place_profile.contact_types.email') }}</div>
                                                <div
                                                    class="mt-0.5"
                                                    data-email-reveal
                                                    data-url="{{ route('places.contact.email', $place->slug) }}"
                                                >
                                                    <button
                                                        type="button"
                                                        data-email-reveal-button
                                                        class="text-sm font-medium underline decoration-zinc-400 underline-offset-2 hover:text-zinc-700 dark:hover:text-zinc-200"
                                                    >
                                                        {{ __('place_profile.show_email') }}
                                                    </button>
                                                    <span data-email-reveal-status class="ml-2 hidden text-xs text-zinc-400"></span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="cw-access-map-shell">
                        <div id="profile-map"></div>
                    </div>
                </div>
            </section>

            <div>
                <section class="rounded-xl border border-zinc-200 bg-white p-3 sm:p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold">{{ __('place_profile.opening_hours') }}</h2>
                            <div class="mt-1 text-xs text-zinc-400">
                                {{ $openingPeriods->isNotEmpty() ? trans_choice('place_profile.opening_periods', $openingPeriods->count(), ['count' => $openingPeriods->count()]) : __('place_profile.no_opening_hours') }}
                            </div>
                        </div>
                        @if ($canDirectEdit || $canSuggest)
                            <a
                                href="{{ route('places.opening-hours.edit', $place->slug) }}"
                                class="cw-profile-edit-action inline-flex size-7 shrink-0 items-center justify-center rounded text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200"
                                title="{{ $canDirectEdit ? __('place_profile.edit_opening_hours') : __('place_profile.suggest_opening_hours') }}"
                                aria-label="{{ $canDirectEdit ? __('place_profile.edit_opening_hours') : __('place_profile.suggest_opening_hours') }}"
                            >
                                <x-tabler-icon name="pencil" class="size-3.5" />
                            </a>
                        @endif
                    </div>

                    @if ($openingPeriods->isEmpty())
                        <p class="mt-4 text-sm italic text-zinc-400">{{ __('place_profile.no_information') }}</p>
                    @else
                        @php
                            $weekdayLabels = __('place_profile.weekdays');
                        @endphp

                        <div class="mt-4 space-y-5">
                            @foreach ($openingPeriods as $period)
                                @php
                                    $weekdayHours = $period->hours
                                        ->where('day_type', 'weekday')
                                        ->groupBy(fn ($row) => (int) $row->weekday);
                                    $holidayHours = $period->hours->where('day_type', 'holiday')->values();
                                @endphp

                                <div class="rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div class="mb-2 text-sm font-semibold">{{ $period->label }}</div>
                                    <dl class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                                        @foreach ($weekdayLabels as $weekday => $weekdayLabel)
                                            @php
                                                $rows = $weekdayHours->get($weekday, collect());
                                            @endphp
                                            <div class="flex justify-between gap-5 py-2 first:pt-0">
                                                <dt class="text-zinc-500">{{ $weekdayLabel }}</dt>
                                                <dd class="text-right font-medium">
                                                    @if ($rows->isEmpty())
                                                        <span class="italic text-zinc-400">{{ __('place_profile.unknown') }}</span>
                                                    @else
                                                        @foreach ($rows as $row)
                                                            <div>
                                                                @if ($row->is_not_provided_by_operator)
                                                                    {{ __('place_profile.operator_not_provided') }}
                                                                @elseif ($row->is_closed)
                                                                    {{ __('place_profile.closed') }}
                                                                @elseif ($row->is_24_hours)
                                                                    {{ __('place_profile.open_24_hours') }}
                                                                @elseif ($row->by_appointment_only)
                                                                    {{ __('place_profile.appointment_only') }}
                                                                @elseif ($row->opens_at && $row->closes_at)
                                                                    {{ substr($row->opens_at, 0, 5) }}–{{ substr($row->closes_at, 0, 5) }}
                                                                @else
                                                                    <span class="italic text-zinc-400">{{ __('place_profile.unknown') }}</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </dd>
                                            </div>
                                        @endforeach

                                        <div class="flex justify-between gap-5 py-2">
                                            <dt class="text-zinc-500">{{ __('place_profile.holidays') }}</dt>
                                            <dd class="text-right font-medium">
                                                @if ($holidayHours->isEmpty())
                                                    <span class="italic text-zinc-400">{{ __('place_profile.unknown') }}</span>
                                                @else
                                                    @foreach ($holidayHours as $row)
                                                        <div>
                                                            @if ($row->is_not_provided_by_operator)
                                                                {{ __('place_profile.operator_not_provided') }}
                                                            @elseif ($row->is_closed)
                                                                {{ __('place_profile.closed') }}
                                                            @elseif ($row->is_24_hours)
                                                                {{ __('place_profile.open_24_hours') }}
                                                            @elseif ($row->by_appointment_only)
                                                                {{ __('place_profile.appointment_only') }}
                                                            @elseif ($row->opens_at && $row->closes_at)
                                                                {{ substr($row->opens_at, 0, 5) }}–{{ substr($row->closes_at, 0, 5) }}
                                                            @else
                                                                <span class="italic text-zinc-400">{{ __('place_profile.unknown') }}</span>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </dd>
                                        </div>
                                    </dl>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <p class="mt-3 text-xs text-zinc-400">
                        {{ __('place_profile.holiday_note') }}
                    </p>
                </section>

            </div>

        @php
    if ($profileEnabled ?? false) {
        $__now = microtime(true);
        $__profileSections['Before features'] = round(($__now - $__profileSectionLast) * 1000, 2);
        $__profileSectionLast = $__now;
    }
@endphp

        <section id="features" class="scroll-mt-24 rounded-xl border border-zinc-200 bg-white p-3 sm:p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold">{{ __('place_profile.features') }}</h2>
                    <div class="mt-1 text-xs text-zinc-400">{{ __('place_profile.features_known', ['known' => $knownFeatureCount, 'total' => $featureCount]) }}</div>
                </div>
            </div>

            @php
                $hasExtraFeatureGroups = $featureGroups->contains(
                    fn ($group) => !($group->is_standard ?? false) && !($group->has_known_features ?? false)
                );
            @endphp

            <div class="space-y-4" data-place-feature-groups>
                @foreach ($featureGroups as $group)
                    @include('places._feature-category', ['group' => $group])
                @endforeach
            </div>

            @if ($hasExtraFeatureGroups && ($canDirectEdit || $canSuggest))
                <button
                    type="button"
                    data-more-feature-groups
                    data-show-label="{{ __('feature_workflow.more_features') }}"
                    data-hide-label="{{ __('feature_workflow.hide_more_features') }}"
                    class="mt-4 inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-2 text-xs font-medium hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
                >
                    <x-tabler-icon name="plus" class="size-3.5" />
                    <span>{{ __('feature_workflow.more_features') }}</span>
                </button>
            @endif
        </section>


            @php
    if ($profileEnabled ?? false) {
        $__now = microtime(true);
        $__profileSections['Feature groups'] = round(($__now - $__profileSectionLast) * 1000, 2);
        $__profileSectionLast = $__now;
    }
@endphp

            @if ($placeGalleryPhotos->total() > 0)
                <section id="photos" class="scroll-mt-24 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                    <div class="flex items-baseline justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-semibold">{{ __('place_profile.photos') }}</h2>
                            <p class="mt-1 text-sm text-zinc-500">{{ __('place_profile.photos_intro') }}</p>
                        </div>
                        <span class="text-sm text-zinc-400">{{ $placeGalleryPhotos->total() }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6">
                        @foreach ($placeGalleryPhotos as $photo)
                            @php
                                $isExternalPhoto = ! empty($photo->external_source_id);
                            @endphp
                            <div id="photo-{{ $photo->id }}" class="scroll-mt-24 rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800">
                                <button
                                    type="button"
                                    data-photo-lightbox
                                    data-photo-src="{{ route('photos.show', ['uuid' => $photo->uuid, 'variant' => 'detail']) }}"
                                    data-photo-alt="{{ __('place_profile.photo_alt', ['place' => $place->name]) }}"
                                    data-photo-caption="{{ $photo->source_license_code
                                        ? __('place_profile.external_photo_credit', ['author' => $photo->source_author ?: $photo->source_provider, 'license' => $photo->source_license_code])
                                        : trans_choice('place_profile.helpful_votes', $photo->helpful_count, ['count' => $photo->helpful_count]) }}"
                                    class="group block w-full overflow-hidden rounded-t-lg text-left"
                                >
                                    <img src="{{ route('photos.show', ['uuid' => $photo->uuid, 'variant' => 'preview']) }}" alt="{{ __('place_profile.photo_alt', ['place' => $place->name]) }}" loading="lazy" class="aspect-[4/3] w-full object-cover transition duration-200 group-hover:scale-[1.03]">
                                </button>
                                <div class="flex flex-wrap items-center gap-1 px-2 py-1.5 text-[11px] text-zinc-500">
                                    @if ($isExternalPhoto)
                                        <span class="mr-auto">{{ __('place_profile.external_photo_credit', ['author' => $photo->source_author ?: $photo->source_provider, 'license' => $photo->source_license_code]) }}</span>
                                        @if ($photo->source_license_url)
                                            <a href="{{ $photo->source_license_url }}" target="_blank" rel="noopener" class="underline">{{ __('place_profile.license') }}</a>
                                        @endif
                                    @else
                                        <span class="mr-auto">{{ __('place_profile.helpful', ['count' => $photo->helpful_count]) }}</span>
                                        @auth
                                            @if ((int) $photo->user_id !== (int) auth()->id())
                                                <form method="POST" action="{{ $photo->viewer_voted ? route('photos.helpful.destroy', $photo->id) : route('photos.helpful.store', $photo->id) }}">
                                                    @csrf
                                                    @if ($photo->viewer_voted)
                                                        @method('DELETE')
                                                    @endif
                                                    <button type="submit" class="rounded px-1.5 py-1 font-medium text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                                        {{ $photo->viewer_voted ? __('photos.helpful_selected') : __('photos.helpful') }}
                                                    </button>
                                                </form>
                                                <details class="relative">
                                                    <summary class="cursor-pointer list-none rounded px-1.5 py-1 text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-700">{{ __('photos.report') }}</summary>
                                                    <form method="POST" action="{{ route('photos.report', $photo->id) }}" class="absolute right-0 top-7 z-20 w-64 rounded-lg border border-zinc-200 bg-white p-3 shadow-xl dark:border-zinc-700 dark:bg-zinc-900">
                                                        @csrf
                                                        <select name="reason" required class="w-full rounded border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-950">
                                                            @foreach (['wrong_place', 'privacy', 'inappropriate', 'spam', 'copyright', 'other'] as $reason)
                                                                <option value="{{ $reason }}">{{ __("photos.report_reasons.{$reason}") }}</option>
                                                            @endforeach
                                                        </select>
                                                        <textarea name="comment" rows="2" maxlength="1000" placeholder="{{ __('photos.report_comment_placeholder') }}" class="mt-2 w-full rounded border border-zinc-300 bg-white p-2 text-xs dark:border-zinc-700 dark:bg-zinc-950"></textarea>
                                                        <button type="submit" class="mt-2 rounded bg-zinc-900 px-2 py-1.5 font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('photos.report_submit') }}</button>
                                                    </form>
                                                </details>
                                            @endif
                                        @endauth
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if ($placeGalleryPhotos->hasPages())
                        <div class="mt-5">{{ $placeGalleryPhotos->links() }}</div>
                    @endif
                </section>
            @endif

            @php
    if ($profileEnabled ?? false) {
        $__now = microtime(true);
        $__profileSections['Photo gallery'] = round(($__now - $__profileSectionLast) * 1000, 2);
        $__profileSectionLast = $__now;
    }
@endphp

            @include('places._reviews')
            @php
    if ($profileEnabled ?? false) {
        $__now = microtime(true);
        $__profileSections['Reviews'] = round(($__now - $__profileSectionLast) * 1000, 2);
        $__profileSectionLast = $__now;
    }
@endphp

            <dialog data-photo-dialog class="m-auto max-h-[90vh] w-[min(92vw,1100px)] rounded-xl border border-zinc-700 bg-zinc-950 p-0 text-white shadow-2xl backdrop:bg-black/75">
                <div class="relative p-3 sm:p-5">
                    <button type="button" data-photo-dialog-close aria-label="{{ __('place_profile.close_image') }}" class="absolute right-4 top-4 z-10 grid size-9 place-items-center rounded-full bg-black/70 text-xl text-white hover:bg-black">×</button>
                    <img data-photo-dialog-image alt="" class="mx-auto max-h-[78vh] max-w-full rounded-lg object-contain">
                    <div data-photo-dialog-caption class="mt-3 min-h-5 text-center text-sm text-zinc-300"></div>
                </div>
            </dialog>
        </div>
    </div>
    @if ($profileEnabled ?? false)
        @php
            $__now = microtime(true);
            $__profileSections['After reviews'] = round(($__now - $__profileSectionLast) * 1000, 2);
            $__profileSectionLast = $__now;
            $__profileSlotMs = round(($__now - $__profileSlotStarted) * 1000, 2);
        @endphp
        <div class="mx-auto mb-4 w-full max-w-7xl border border-sky-300 bg-sky-50 px-4 py-3 text-xs text-sky-950 dark:border-sky-800 dark:bg-sky-950/30 dark:text-sky-100">
            @foreach ($__profileSections as $label => $milliseconds)
                <div class="flex justify-between gap-3"><span>{{ $label }}</span><strong>{{ number_format($milliseconds, 2, ',', '.') }} ms</strong></div>
            @endforeach
            <div class="mt-1 flex justify-between gap-3 border-t border-sky-300 pt-1 dark:border-sky-800"><span>Blade page slot</span><strong>{{ number_format($__profileSlotMs, 2, ',', '.') }} ms</strong></div>
        </div>
    @endif
</x-layouts::app>
@if ($profileEnabled ?? false)
    @php($__profileBladeMs = round((microtime(true) - $__profileRenderStarted) * 1000, 2))
    <div class="mx-auto mb-4 w-full max-w-7xl border border-sky-400 bg-sky-100 px-4 py-3 text-xs text-sky-950 dark:border-sky-700 dark:bg-sky-950/40 dark:text-sky-100">
        <div class="flex justify-between gap-3"><span>Blade total incl. layout</span><strong>{{ number_format($__profileBladeMs, 2, ',', '.') }} ms</strong></div>
    </div>
@endif
