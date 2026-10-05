@php
    $article = app(\App\Services\LocalizedSupportContentService::class)
        ->articles()
        ->where('sa.slug', 'regeln-und-empfehlungen-fuer-rezensionen')
        ->where('sa.is_active', true)
        ->first();

    $modalName = 'review-content-rules';
@endphp

@if ($article)
    <div class="rounded-lg border border-sky-200 bg-sky-50/70 p-3 text-sm text-sky-950 dark:border-sky-900 dark:bg-sky-950/25 dark:text-sky-100">
        <div class="font-medium">{{ __('reviews.content_rules.quick_title') }}</div>
        <p class="mt-1 leading-5">{{ __('reviews.content_rules.quick_text') }}</p>

        <div class="mt-2">
            <flux:modal.trigger :name="$modalName">
                <button type="button" class="text-sm font-semibold underline underline-offset-2 hover:no-underline">
                    {{ __('reviews.content_rules.show_all') }}
                </button>
            </flux:modal.trigger>
        </div>
    </div>

    <flux:modal :name="$modalName" class="max-w-3xl">
        <div class="space-y-4">
            <div>
                <flux:heading size="lg">{{ $article->title }}</flux:heading>
                @if ($article->summary)
                    <flux:text class="mt-1">{{ $article->summary }}</flux:text>
                @endif
            </div>

            <div class="max-h-[65vh] overflow-y-auto whitespace-pre-line pr-2 text-sm leading-6 text-zinc-700 dark:text-zinc-300">
                {{ $article->body }}
            </div>
        </div>
    </flux:modal>
@endif
