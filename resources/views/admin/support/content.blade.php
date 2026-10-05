<x-layouts::app :title="__('admin.support.content_title')">
    @php
        $typeLabels = __('support.roadmap_types');
        $statusLabels = collect(__('support.statuses'))->only(['reported', 'confirmed', 'suggested', 'planned', 'in_progress', 'resolved', 'closed', 'not_planned'])->all();
    @endphp
    <div class="mx-auto w-full max-w-7xl space-y-8 px-5 py-8">
        <div>
            <a href="{{ route('admin.support.index') }}" class="text-sm text-zinc-500 hover:text-zinc-950 dark:hover:text-white">← {{ __('admin.support.title') }}</a>
            <h1 class="mt-3 text-3xl font-semibold">{{ __('admin.support.content_title') }}</h1>
            <p class="mt-2 text-zinc-500">{{ __('admin.support.content_intro') }}</p>
        </div>
<section class="space-y-4">
            <h2 class="text-xl font-semibold">{{ __('admin.support.new_article') }}</h2>
            <form method="POST" action="{{ route('admin.support.articles.store') }}" class="grid gap-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                @csrf
                <input name="context_key" placeholder="{{ __('admin.support.context_key_example') }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                @foreach ($locales as $locale => $localeConfig)
                    <fieldset class="grid gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <legend class="px-2 text-sm font-semibold">{{ $localeConfig['flag'] ?? '' }} {{ $localeConfig['native_name'] ?? strtoupper($locale) }}</legend>
                        <input name="translations[{{ $locale }}][title]" @required($locale === $contentSourceLocale) placeholder="{{ __('admin.support.localized_placeholder', ['field' => __('admin.support.article_title'), 'locale' => strtoupper($locale)]) }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <input name="translations[{{ $locale }}][summary]" placeholder="{{ __('admin.support.localized_placeholder', ['field' => __('admin.support.short_summary'), 'locale' => strtoupper($locale)]) }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <textarea name="translations[{{ $locale }}][body]" rows="8" data-help-link-editor @required($locale === $contentSourceLocale) placeholder="{{ __('admin.support.localized_placeholder', ['field' => __('admin.support.article_body'), 'locale' => strtoupper($locale)]) }}" class="rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                    </fieldset>
                @endforeach
                <input name="sort_order" type="number" value="100" class="h-10 w-32 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                <button class="w-fit rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin.support.create_article') }}</button>
            </form>

            <div class="space-y-3">
                @foreach ($articles as $article)
                    <details class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <summary class="cursor-pointer font-semibold">{{ $article->title }} <span class="text-xs font-normal text-zinc-500">· {{ $article->context_key ?: __('admin.support.general') }} · {{ $article->is_active ? __('admin.support.active') : __('admin.support.inactive') }}</span></summary>
                        <form method="POST" action="{{ route('admin.support.articles.update', $article->id) }}" class="mt-4 grid gap-3">
                            @csrf @method('PUT')
                            <input name="context_key" value="{{ $article->context_key }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                            @foreach ($locales as $locale => $localeConfig)
                                @php($translation = $article->translations->get($locale))
                                <fieldset class="grid gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <legend class="px-2 text-sm font-semibold">{{ $localeConfig['flag'] ?? '' }} {{ $localeConfig['native_name'] ?? strtoupper($locale) }}</legend>
                                    <input name="translations[{{ $locale }}][title]" value="{{ $translation?->title }}" @required($locale === $contentSourceLocale) placeholder="{{ __('admin.support.article_title') }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                                    <input name="translations[{{ $locale }}][summary]" value="{{ $translation?->summary }}" placeholder="{{ __('admin.support.short_summary') }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                                    <textarea name="translations[{{ $locale }}][body]" rows="8" data-help-link-editor @required($locale === $contentSourceLocale) placeholder="{{ __('admin.support.article_body') }}" class="rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">{{ $translation?->body }}</textarea>
                                </fieldset>
                            @endforeach
                            <div class="flex flex-wrap items-center gap-4">
                                <input name="sort_order" type="number" value="{{ $article->sort_order }}" class="h-10 w-28 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($article->is_active)> {{ __('admin.support.active') }}</label>
                                <button class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">{{ __('admin.support.save') }}</button>
                            </div>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>

        <section class="space-y-4 border-t border-zinc-200 pt-8 dark:border-zinc-800">
            <h2 class="text-xl font-semibold">{{ __('support.roadmap.title') }}</h2>
            <form method="POST" action="{{ route('admin.support.entries.store') }}" class="grid gap-4 rounded-xl border border-zinc-200 p-5 dark:border-zinc-700">
                @csrf
                <div class="grid gap-4 md:grid-cols-3">
                    <select name="type" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">@foreach($typeLabels as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                    <select name="status" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">@foreach($statusLabels as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
                    <input name="context_key" placeholder="{{ __('admin.support.context_key_optional') }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                </div>
                @foreach ($locales as $locale => $localeConfig)
                    <fieldset class="grid gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <legend class="px-2 text-sm font-semibold">{{ $localeConfig['flag'] ?? '' }} {{ $localeConfig['native_name'] ?? strtoupper($locale) }}</legend>
                        <input name="translations[{{ $locale }}][title]" @required($locale === $contentSourceLocale) placeholder="{{ __('admin.support.localized_placeholder', ['field' => __('admin.support.entry_title'), 'locale' => strtoupper($locale)]) }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                        <textarea name="translations[{{ $locale }}][description]" rows="5" placeholder="{{ __('admin.support.localized_placeholder', ['field' => __('admin.support.public_description'), 'locale' => strtoupper($locale)]) }}" class="rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900"></textarea>
                    </fieldset>
                @endforeach
                <div class="flex flex-wrap items-center gap-4">
                    <input name="sort_order" type="number" value="100" class="h-10 w-28 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1"> {{ __('admin.support.public') }}</label>
                    <button class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-zinc-950">{{ __('admin.support.create_entry') }}</button>
                </div>
            </form>

            <div class="space-y-3">
                @foreach ($entries as $entry)
                    <details class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <summary class="cursor-pointer font-semibold">{{ $entry->title }} <span class="text-xs font-normal text-zinc-500">· {{ $typeLabels[$entry->type] ?? $entry->type }} · {{ $statusLabels[$entry->status] ?? $entry->status }} · {{ $entry->is_public ? __('admin.support.public') : __('admin.support.internal') }}</span></summary>
                        <form method="POST" action="{{ route('admin.support.entries.update', $entry->id) }}" class="mt-4 grid gap-3">
                            @csrf @method('PUT')
                            <div class="grid gap-3 md:grid-cols-3">
                                <select name="type" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">@foreach($typeLabels as $key=>$label)<option value="{{ $key }}" @selected($entry->type===$key)>{{ $label }}</option>@endforeach</select>
                                <select name="status" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">@foreach($statusLabels as $key=>$label)<option value="{{ $key }}" @selected($entry->status===$key)>{{ $label }}</option>@endforeach</select>
                                <input name="context_key" value="{{ $entry->context_key }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                            </div>
                            @foreach ($locales as $locale => $localeConfig)
                                @php($translation = $entry->translations->get($locale))
                                <fieldset class="grid gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <legend class="px-2 text-sm font-semibold">{{ $localeConfig['flag'] ?? '' }} {{ $localeConfig['native_name'] ?? strtoupper($locale) }}</legend>
                                    <input name="translations[{{ $locale }}][title]" value="{{ $translation?->title }}" @required($locale === $contentSourceLocale) placeholder="{{ __('admin.support.entry_title') }}" class="h-10 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                                    <textarea name="translations[{{ $locale }}][description]" rows="5" placeholder="{{ __('admin.support.public_description') }}" class="rounded-lg border border-zinc-300 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-900">{{ $translation?->description }}</textarea>
                                </fieldset>
                            @endforeach
                            <div class="flex flex-wrap items-center gap-4">
                                <input name="sort_order" type="number" value="{{ $entry->sort_order }}" class="h-10 w-28 rounded-lg border border-zinc-300 bg-white px-3 dark:border-zinc-700 dark:bg-zinc-900">
                                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_public" value="1" @checked($entry->is_public)> {{ __('admin.support.public') }}</label>
                                <button class="rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium dark:border-zinc-700">{{ __('admin.support.save') }}</button>
                            </div>
                        </form>
                    </details>
                @endforeach
            </div>
        </section>
    </div>

    @push('scripts')
        <script>
            (() => {
                const articles = @json($articles->map(fn ($article) => [
                    'slug' => $article->slug,
                    'title' => $article->translations->get(app()->getLocale())?->title ?: $article->title,
                ])->values());

                let menu = null;
                let activeEditor = null;
                let linkStart = null;

                const closeMenu = () => {
                    menu?.remove();
                    menu = null;
                    activeEditor = null;
                    linkStart = null;
                };

                const insertArticleLink = (article) => {
                    if (!activeEditor || linkStart === null) return;

                    const cursor = activeEditor.selectionStart;
                    const value = activeEditor.value;
                    const replacement = `[[${article.slug}|${article.title}]]`;
                    activeEditor.value = value.slice(0, linkStart) + replacement + value.slice(cursor);

                    const newCursor = linkStart + replacement.length;
                    activeEditor.focus();
                    activeEditor.setSelectionRange(newCursor, newCursor);
                    activeEditor.dispatchEvent(new Event('input', { bubbles: true }));
                    closeMenu();
                };

                const showMenu = (editor, start, query) => {
                    const normalizedQuery = query.toLocaleLowerCase();
                    const matches = articles
                        .filter((article) => article.title.toLocaleLowerCase().includes(normalizedQuery) || article.slug.includes(normalizedQuery))
                        .slice(0, 8);

                    closeMenu();
                    if (!matches.length) return;

                    activeEditor = editor;
                    linkStart = start;
                    menu = document.createElement('div');
                    menu.className = 'fixed z-[2000] max-h-72 w-80 overflow-y-auto rounded-lg border border-zinc-200 bg-white p-1 shadow-xl dark:border-zinc-700 dark:bg-zinc-900';

                    const rect = editor.getBoundingClientRect();
                    menu.style.left = `${Math.min(rect.left, window.innerWidth - 336)}px`;
                    menu.style.top = `${Math.min(rect.bottom + 4, window.innerHeight - 300)}px`;

                    matches.forEach((article) => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'block w-full rounded-md px-3 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800';
                        button.innerHTML = `<span class="block text-sm font-medium"></span><span class="block text-xs text-zinc-500"></span>`;
                        button.children[0].textContent = article.title;
                        button.children[1].textContent = article.slug;
                        button.addEventListener('mousedown', (event) => {
                            event.preventDefault();
                            insertArticleLink(article);
                        });
                        menu.appendChild(button);
                    });

                    document.body.appendChild(menu);
                };

                document.querySelectorAll('[data-help-link-editor]').forEach((editor) => {
                    editor.addEventListener('input', () => {
                        const cursor = editor.selectionStart;
                        const beforeCursor = editor.value.slice(0, cursor);
                        const start = beforeCursor.lastIndexOf('[');

                        if (start < 0 || beforeCursor.slice(start).includes(']') || beforeCursor.slice(start).includes('\n')) {
                            closeMenu();
                            return;
                        }

                        const query = beforeCursor.slice(start + 1).replace(/^\[/, '');
                        showMenu(editor, start, query);
                    });

                    editor.addEventListener('keydown', (event) => {
                        if (event.key === 'Escape') closeMenu();
                    });
                });

                document.addEventListener('mousedown', (event) => {
                    if (menu && !menu.contains(event.target) && event.target !== activeEditor) closeMenu();
                });
            })();
        </script>
    @endpush
</x-layouts::app>
