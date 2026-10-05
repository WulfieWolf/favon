@php
    $dialog = session('ui_dialog');
    $toast = session('ui_toast');
    $errorBag = $errors ?? null;
    $validationMessages = $errorBag?->all() ?? [];
    $validationFields = $errorBag ? collect($errorBag->keys())->values()->all() : [];
    $hasValidationErrors = count($validationMessages) > 0;
    $hasDialog = is_array($dialog) && filled($dialog['message'] ?? null);

    $variant = $hasValidationErrors ? 'error' : ($dialog['variant'] ?? 'success');
    $defaultTitle = match ($variant) {
        'warning' => __('ui.feedback.warning_title'),
        'info' => __('ui.feedback.info_title'),
        default => __('ui.feedback.success_title'),
    };
    $title = $hasValidationErrors
        ? __('ui.feedback.validation_title')
        : ($dialog['title'] ?? $defaultTitle);
    $message = $hasValidationErrors
        ? __('ui.feedback.validation_message')
        : ($dialog['message'] ?? '');
    $details = $hasValidationErrors
        ? $validationMessages
        : array_values(array_filter((array) ($dialog['details'] ?? [])));
@endphp

@php
    $toastData = is_array($toast)
        ? $toast
        : (filled($toast) ? ['variant' => 'success', 'message' => $toast] : null);
@endphp

@if ($toastData && ! $hasValidationErrors && ! $hasDialog)
    <div
        data-cw-toast
        data-timeout="{{ (int) ($toastData['timeout'] ?? 4500) }}"
        class="fixed bottom-4 left-1/2 flex w-[calc(100%-2rem)] max-w-md -translate-x-1/2 items-start gap-3 rounded-xl border border-zinc-200 bg-white px-4 py-3 text-sm text-zinc-800 shadow-2xl sm:left-auto sm:right-4 sm:w-auto sm:min-w-80 sm:translate-x-0 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
        style="z-index:2147483646"
        role="status"
        aria-live="polite"
    >
        <div
            @class([
                'mt-0.5 grid size-7 shrink-0 place-items-center rounded-full',
                'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300' => ($toastData['variant'] ?? 'success') === 'error',
                'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' => ($toastData['variant'] ?? 'success') === 'warning',
                'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' => ($toastData['variant'] ?? 'success') === 'info',
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' => ! in_array(($toastData['variant'] ?? 'success'), ['error', 'warning', 'info'], true),
            ])
        >
            <x-tabler-icon :name="($toastData['variant'] ?? 'success') === 'error' ? 'x' : (($toastData['variant'] ?? 'success') === 'success' ? 'circle-check' : 'info-circle')" class="size-4" />
        </div>
        <div class="min-w-0 flex-1 leading-6">{{ $toastData['message'] ?? '' }}</div>
        <button type="button" data-cw-toast-close class="grid size-7 shrink-0 place-items-center rounded-md text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" aria-label="{{ __('ui.feedback.close') }}">×</button>
    </div>

    <script>
        (() => {
            const toast = document.querySelector('[data-cw-toast]');
            if (!toast || toast.dataset.bound) return;
            toast.dataset.bound = '1';

            const close = () => toast.remove();
            toast.querySelector('[data-cw-toast-close]')?.addEventListener('click', close);
            window.setTimeout(close, Number.parseInt(toast.dataset.timeout || '4500', 10));
        })();
    </script>
@endif

@if ($hasValidationErrors || $hasDialog)
    <div
        data-cw-feedback
        data-variant="{{ $variant }}"
        data-error-fields='@json($hasValidationErrors ? $validationFields : [])'
        class="fixed inset-0 flex items-center justify-center bg-black/60 p-4 backdrop-blur-[1px]"
        style="z-index:2147483647"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cw-feedback-title"
        aria-describedby="cw-feedback-message"
    >
        <div class="w-full max-w-lg rounded-2xl border border-zinc-200 bg-white shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-start gap-4 p-5 sm:p-6">
                <div
                    @class([
                        'mt-0.5 grid size-10 shrink-0 place-items-center rounded-full',
                        'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300' => $variant === 'error',
                        'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' => $variant === 'warning',
                        'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300' => $variant === 'info',
                        'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' => ! in_array($variant, ['error', 'warning', 'info'], true),
                    ])
                >
                    <x-tabler-icon :name="$variant === 'error' ? 'x' : ($variant === 'success' ? 'circle-check' : 'info-circle')" class="size-5" />
                </div>

                <div class="min-w-0 flex-1">
                    <h2 id="cw-feedback-title" class="text-lg font-semibold text-zinc-950 dark:text-white">{{ $title }}</h2>
                    <p id="cw-feedback-message" class="mt-1.5 text-sm leading-6 text-zinc-600 dark:text-zinc-300">{{ $message }}</p>

                    @if ($details !== [])
                        <ul class="mt-4 max-h-56 space-y-2 overflow-y-auto border-t border-zinc-200 pt-3 text-sm leading-6 text-zinc-700 dark:border-zinc-700 dark:text-zinc-200">
                            @foreach ($details as $detail)
                                <li class="flex items-start gap-2.5">
                                    <span
                                        class="mt-[0.55rem] size-1.5 shrink-0 rounded-full"
                                        style="background-color:{{ $variant === 'error' ? '#ef4444' : '#a1a1aa' }}"
                                        aria-hidden="true"
                                    ></span>
                                    <span>{{ $detail }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="flex justify-end border-t border-zinc-200 px-5 py-4 dark:border-zinc-800">
                <button
                    type="button"
                    data-cw-feedback-close
                    class="rounded-lg bg-zinc-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200"
                >
                    {{ __('ui.feedback.ok') }}
                </button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const initFeedback = () => {
                const dialog = document.querySelector('[data-cw-feedback]');
                if (!dialog || dialog.dataset.bound) return;
                dialog.dataset.bound = '1';

                let fields = [];
                try {
                    fields = JSON.parse(dialog.dataset.errorFields || '[]');
                } catch (_) {}

                const bracketName = (key) => {
                    const parts = String(key).split('.');
                    if (parts.length === 0) return String(key);

                    return parts[0] + parts.slice(1).map((part) => '[' + part + ']').join('');
                };

                const candidateNames = (key) => {
                    const names = [String(key), bracketName(key)];
                    const parts = String(key).split('.');

                    if (parts.some((part) => /^\\d+$/.test(part))) {
                        names.push(parts[0] + parts.slice(1).map((part) => /^\\d+$/.test(part) ? '[]' : '[' + part + ']').join(''));
                    }

                    return [...new Set(names)];
                };

                const findField = (key) => {
                    for (const name of candidateNames(key)) {
                        const field = [...document.querySelectorAll('[name]')].find((candidate) => candidate.getAttribute('name') === name);
                        if (field) return field;
                    }

                    return null;
                };

                const markField = (field) => {
                    if (!field) return;

                    const target = field.type === 'radio' || field.type === 'checkbox'
                        ? (field.closest('fieldset') || field.closest('label') || field)
                        : field;

                    const clearMark = () => {
                        target.classList.remove('ring-2', 'ring-red-500', 'ring-offset-2', 'ring-offset-white', 'dark:ring-offset-zinc-950');
                        field.removeAttribute('aria-invalid');
                    };

                    target.classList.add('ring-2', 'ring-red-500', 'ring-offset-2', 'ring-offset-white', 'dark:ring-offset-zinc-950');
                    field.setAttribute('aria-invalid', 'true');
                    field.addEventListener(field.type === 'radio' || field.type === 'checkbox' ? 'change' : 'input', clearMark, { once: true });
                };

                const matchedFields = fields.map(findField).filter(Boolean);
                const firstField = matchedFields[0] || null;
                matchedFields.forEach(markField);

                if (firstField) {
                    window.requestAnimationFrame(() => {
                        firstField.scrollIntoView({ behavior: 'auto', block: 'center' });
                    });
                }

                const close = () => {
                    dialog.remove();

                    if (firstField) {
                        window.setTimeout(() => {
                            firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            try { firstField.focus({ preventScroll: true }); } catch (_) { firstField.focus(); }
                        }, 40);
                    }
                };

                dialog.querySelector('[data-cw-feedback-close]')?.addEventListener('click', close);

                const escapeHandler = (event) => {
                    if (event.key !== 'Escape' || ! document.body.contains(dialog)) return;
                    document.removeEventListener('keydown', escapeHandler);
                    close();
                };
                document.addEventListener('keydown', escapeHandler);

                window.requestAnimationFrame(() => dialog.querySelector('[data-cw-feedback-close]')?.focus());
            };

            document.addEventListener('DOMContentLoaded', initFeedback, { once: true });
            document.addEventListener('livewire:navigated', initFeedback);
            initFeedback();
        })();
    </script>
@endif


<script data-cw-global-form-validation>
    (() => {
        const enableServerValidation = () => {
            document.querySelectorAll('form[method="POST"], form[method="post"]').forEach((form) => {
                if (! form.hasAttribute('data-native-validation')) {
                    form.noValidate = true;
                }
            });
        };

        document.addEventListener('DOMContentLoaded', enableServerValidation, { once: true });
        document.addEventListener('livewire:navigated', enableServerValidation);
        enableServerValidation();
    })();
</script>
