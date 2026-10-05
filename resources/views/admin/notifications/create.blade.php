<x-layouts::app :title="__('admin.system_notifications.title')">
    <div class="mx-auto w-full max-w-3xl px-5 py-8 xl:px-7">
        <div class="mb-6">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('admin.system_notifications.title') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('admin.system_notifications.intro') }}</p>
        </div>
<form method="POST" action="{{ route('admin.notifications.store') }}" class="space-y-5 rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            @csrf

            <label class="block">
                <span class="mb-1 block text-sm font-medium">{{ __('admin.system_notifications.notification_title') }}</span>
                <input name="title" value="{{ old('title') }}" maxlength="255" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
            </label>

            <label class="block">
                <span class="mb-1 block text-sm font-medium">{{ __('admin.system_notifications.message') }}</span>
                <textarea name="message" rows="5" maxlength="5000" required class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">{{ old('message') }}</textarea>
            </label>

            <div class="grid gap-4 md:grid-cols-2">
                <label class="block">
                    <span class="mb-1 block text-sm font-medium">{{ __('admin.system_notifications.priority') }}</span>
                    <select name="priority" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                        <option value="normal" @selected(old('priority', 'normal') === 'normal')>{{ __('admin.system_notifications.priorities.normal') }}</option>
                        <option value="important" @selected(old('priority') === 'important')>{{ __('admin.system_notifications.priorities.important') }}</option>
                    </select>
                </label>

                <label class="block">
                    <span class="mb-1 block text-sm font-medium">{{ __('admin.system_notifications.expires_at') }} ({{ __('admin.system_notifications.optional') }})</span>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
                </label>
            </div>

            <label class="block">
                <span class="mb-1 block text-sm font-medium">{{ __('admin.system_notifications.link') }} ({{ __('admin.system_notifications.optional') }})</span>
                <input name="url" value="{{ old('url') }}" maxlength="2048" placeholder="https://..." class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-950">
            </label>

            <div class="flex justify-end gap-2">
                <a href="{{ route('admin.index') }}" class="rounded-lg px-4 py-2 text-sm font-medium text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-800">{{ __('admin.system_notifications.cancel') }}</a>
                <button type="submit" class="rounded-lg bg-zinc-900 px-4 py-2 text-sm font-semibold text-white hover:bg-zinc-700 dark:bg-white dark:text-zinc-950 dark:hover:bg-zinc-200">{{ __('admin.system_notifications.publish') }}</button>
            </div>
        </form>
    </div>
</x-layouts::app>
