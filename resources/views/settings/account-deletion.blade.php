<x-layouts::app :title="__('account_deletion.title')">
<section class="w-full">
    @include('partials.settings-heading')

    <x-pages::settings.layout :heading="__('account_deletion.title')" :subheading="__('account_deletion.subtitle')" :wide="true">
        <div class="space-y-6">
            @if (($user->account_status ?? 'active') === 'pending_deletion')
                <div class="rounded-xl border border-amber-300 bg-amber-50 p-5 text-amber-950 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-100">
                    <h2 class="text-lg font-semibold">{{ __('account_deletion.pending_title') }}</h2>
                    <p class="mt-2 text-sm text-amber-900/90 dark:text-amber-200">
                        {{ __('account_deletion.pending_text', ['date' => \App\Support\LocalTime::parse($user->deletion_scheduled_for)->translatedFormat(__('account_deletion.date_format'))]) }}
                    </p>
                    <form method="POST" action="{{ route('account-deletion.cancel') }}" class="mt-4">
                        @csrf
                        <flux:button type="submit" variant="primary" class="dark:ring-1 dark:ring-amber-700">{{ __('account_deletion.cancel_button') }}</flux:button>
                    </form>
                </div>
            @else
                <div class="rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                    <h2 class="text-lg font-semibold">{{ __('account_deletion.what_happens_title') }}</h2>
                    <div class="mt-3 space-y-2 text-sm">
                        <p>{{ __('account_deletion.what_happens_personal') }}</p>
                        <p>{{ __('account_deletion.what_happens_photos') }}</p>
                        <p>{{ __('account_deletion.what_happens_reviews') }}</p>
                        <p>{{ __('account_deletion.what_happens_contributions') }}</p>
                        <p>{{ __('account_deletion.what_happens_tombstone') }}</p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach (['photos', 'reviews', 'places', 'changes'] as $key)
                        <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-800">
                            <div class="text-xs text-zinc-500">{{ __('account_deletion.stats.'.$key) }}</div>
                            <div class="mt-1 text-2xl font-semibold">{{ $statistics[$key] }}</div>
                        </div>
                    @endforeach
                </div>

                <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-red-900 dark:border-red-900 dark:bg-red-950/30 dark:text-red-100">
                    <h2 class="text-lg font-semibold">{{ __('account_deletion.irreversible_title') }}</h2>
                    <p class="mt-2 text-sm text-red-800 dark:text-red-200">{{ __('account_deletion.irreversible_text') }}</p>
                </div>

                <form method="POST" action="{{ route('account-deletion.request') }}" class="space-y-5 rounded-xl border border-zinc-200 p-5 dark:border-zinc-800">
                    @csrf
                    <fieldset class="space-y-3">
                        <legend class="font-semibold">{{ __('account_deletion.mode_title') }}</legend>
                        <label class="flex gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-800">
                            <input type="radio" name="mode" value="grace" checked class="mt-1">
                            <span>
                                <span class="block font-medium">{{ __('account_deletion.mode_grace_title', ['days' => $graceDays]) }}</span>
                                <span class="mt-1 block text-sm text-zinc-500">{{ __('account_deletion.mode_grace_text', ['days' => $graceDays]) }}</span>
                            </span>
                        </label>
                        <label class="flex gap-3 rounded-lg border border-red-200 p-4 dark:border-red-900">
                            <input type="radio" name="mode" value="immediate" class="mt-1">
                            <span>
                                <span class="block font-medium">{{ __('account_deletion.mode_immediate_title') }}</span>
                                <span class="mt-1 block text-sm text-zinc-500">{{ __('account_deletion.mode_immediate_text') }}</span>
                            </span>
                        </label>
                    </fieldset>

                    <flux:input name="password" :label="__('account_deletion.password_label')" type="password" required viewable />
                    @error('password') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <label class="flex gap-3 text-sm">
                        <input type="checkbox" name="confirm" value="1" required class="mt-1">
                        <span>{{ __('account_deletion.confirm_text') }}</span>
                    </label>
                    @error('confirm') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

                    <flux:button type="submit" variant="danger">{{ __('account_deletion.submit') }}</flux:button>
                </form>
            @endif
        </div>
    </x-pages::settings.layout>
</section>

</x-layouts::app>
