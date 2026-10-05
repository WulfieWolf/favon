<x-layouts::app :title="__('admin.users.title')">
    <div class="space-y-6">
        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="xl">{{ __('admin.users.heading') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.users.intro') }}</flux:text>
            </div>
            <a href="{{ route('admin.index') }}" class="text-sm underline">{{ __('admin.users.back_admin') }}</a>
        </div>

        <form method="GET" class="grid gap-3 rounded-xl border border-neutral-200 p-4 md:grid-cols-5 dark:border-neutral-700">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <input type="hidden" name="dir" value="{{ $direction }}">
            <input name="q" value="{{ $search }}" placeholder="{{ __('admin.users.search_placeholder') }}" class="rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900">
            <select name="status" class="rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900"><option value="">{{ __('admin.users.all_statuses') }}</option>@foreach (['active','suspended','pending_deletion','deleted'] as $value)<option value="{{ $value }}" @selected($status === $value)>{{ __('admin.users.account_statuses.'.$value) }}</option>@endforeach</select>
            <select name="role" class="rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900"><option value="">{{ __('admin.users.all_roles') }}</option>@foreach ($filterRoles as $filterRole)<option value="{{ $filterRole->slug }}" @selected($role === $filterRole->slug)>{{ __('admin.users.role_names.'.$filterRole->slug) }}</option>@endforeach</select>
            <select name="verification" class="rounded-lg border border-neutral-300 bg-white px-3 py-2 dark:border-neutral-600 dark:bg-neutral-900"><option value="">{{ __('admin.users.all_verification') }}</option><option value="verified" @selected($verification === 'verified')>{{ __('admin.users.verified') }}</option><option value="unverified" @selected($verification === 'unverified')>{{ __('admin.users.unverified') }}</option></select>
            <button class="rounded-lg border border-neutral-300 px-3 py-2 font-medium dark:border-neutral-600">{{ __('admin.users.filter') }}</button>
        </form>

        @php
            $sortUrl = function (string $column) use ($sort, $direction) {
                $nextDirection = $sort === $column && $direction === 'asc' ? 'desc' : 'asc';
                return request()->fullUrlWithQuery(['sort' => $column, 'dir' => $nextDirection, 'page' => null]);
            };
            $sortIndicator = fn (string $column) => $sort === $column ? ($direction === 'asc' ? '↑' : '↓') : '↕';
        @endphp

        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-neutral-200 text-sm dark:divide-neutral-700">
                    <thead class="bg-neutral-50 dark:bg-neutral-900/50">
                        <tr>
                            <th class="w-16 px-4 py-3 text-left font-semibold"></th>
                            <th class="px-4 py-3 text-left font-semibold"><a href="{{ $sortUrl('name') }}" class="inline-flex items-center gap-1 hover:underline">{{ __('admin.users.name') }} <span class="text-xs text-neutral-400">{{ $sortIndicator('name') }}</span></a></th>
                            <th class="px-4 py-3 text-left font-semibold"><a href="{{ $sortUrl('display_name') }}" class="inline-flex items-center gap-1 hover:underline">{{ __('admin.users.display_name') }} <span class="text-xs text-neutral-400">{{ $sortIndicator('display_name') }}</span></a></th>
                            <th class="px-4 py-3 text-left font-semibold"><a href="{{ $sortUrl('email') }}" class="inline-flex items-center gap-1 hover:underline">{{ __('admin.users.email') }} <span class="text-xs text-neutral-400">{{ $sortIndicator('email') }}</span></a></th>
                            <th class="px-4 py-3 text-left font-semibold"><a href="{{ $sortUrl('status') }}" class="inline-flex items-center gap-1 hover:underline">{{ __('admin.users.status') }} <span class="text-xs text-neutral-400">{{ $sortIndicator('status') }}</span></a></th>
                            <th class="px-4 py-3 text-left font-semibold"><a href="{{ $sortUrl('role') }}" class="inline-flex items-center gap-1 hover:underline">{{ __('admin.users.roles') }} <span class="text-xs text-neutral-400">{{ $sortIndicator('role') }}</span></a></th>
                            <th class="px-4 py-3 text-left font-semibold"><a href="{{ $sortUrl('last_seen') }}" class="inline-flex items-center gap-1 hover:underline">{{ __('admin.users.last_seen') }} <span class="text-xs text-neutral-400">{{ $sortIndicator('last_seen') }}</span></a></th>
                            <th class="px-4 py-3 text-left font-semibold"><a href="{{ $sortUrl('created') }}" class="inline-flex items-center gap-1 hover:underline">{{ __('admin.users.created') }} <span class="text-xs text-neutral-400">{{ $sortIndicator('created') }}</span></a></th>
                            <th class="px-4 py-3 text-right font-semibold"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-200 dark:divide-neutral-700">
                        @foreach ($users as $user)
                            <tr>
                                <td class="px-4 py-3">
                                    @if ($user->profile_photo_id)
                                        <img src="{{ route('admin.users.photo', $user) }}" alt="" class="size-10 rounded-full object-cover" loading="lazy">
                                    @else
                                        <div class="grid size-10 place-items-center rounded-full bg-neutral-100 text-sm font-semibold text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400">
                                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                                <td class="px-4 py-3">{{ $user->public_alias ?: ($user->public_handle ?: $user->name) }}</td>
                                <td class="px-4 py-3">{{ $user->email }}</td>
                                <td class="px-4 py-3">{{ __('admin.users.account_statuses.'.$user->account_status) }}</td>
                                <td class="px-4 py-3">
                                    @forelse ($roleRows->get($user->id, collect()) as $role)
                                        <span class="mr-1 inline-flex rounded-full bg-neutral-100 px-2 py-1 text-xs dark:bg-neutral-800">{{ __('admin.users.role_names.'.$role->slug) }}</span>
                                    @empty
                                        <span class="text-neutral-500">{{ __('admin.users.none') }}</span>
                                    @endforelse
                                </td>
                                <td class="px-4 py-3 text-neutral-500">{{ $user->last_seen_at ? \App\Support\LocalTime::format($user->last_seen_at, app()->getLocale() === 'de' ? 'd.m.Y H:i' : 'Y-m-d H:i') : __('admin.users.none') }}</td>
                                <td class="px-4 py-3 text-neutral-500">{{ \App\Support\LocalTime::format($user->created_at, app()->getLocale() === 'de' ? 'd.m.Y H:i' : 'Y-m-d H:i') }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('admin.users.show', $user) }}" class="font-medium underline">{{ __('admin.users.manage') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{ $users->links() }}
    </div>
</x-layouts::app>
