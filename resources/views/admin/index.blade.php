<x-layouts::app :title="__('admin.title')">
    <div class="space-y-8">
        <div>
            <flux:heading size="xl">{{ __('admin.title') }}</flux:heading>
            <flux:text class="mt-1">{{ __('admin.overview.intro') }}</flux:text>
        </div>

        @if ($isOwner)
            <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-950/30">
                <div class="font-semibold text-amber-900 dark:text-amber-100">{{ __('admin.overview.owner_title') }}</div>
                <div class="mt-1 text-sm text-amber-800 dark:text-amber-200">
                    {{ __('admin.overview.owner_help') }}
                </div>
            </div>
        @endif

        @php
            $cardClass = 'block rounded-xl border border-neutral-200 p-5 transition hover:bg-neutral-50 dark:border-neutral-700 dark:hover:bg-neutral-800';
            $neutralBadge = 'rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-semibold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300';
            $openBadge = 'rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800 dark:bg-amber-950/50 dark:text-amber-200';
            $doneBadge = 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-200';
        @endphp

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('admin.overview.sections.administration') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.overview.sections.administration_help') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @if ($canViewUsers)
                    <a href="{{ route('admin.users.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.users_rights') }}</div>
                            <span class="{{ $neutralBadge }}">{{ __('admin.overview.total_count', ['count' => $userCount]) }}</span>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.users_rights_help') }}</div>
                    </a>
                @endif

                @if ($canMergePlaces)
                    <a href="{{ route('admin.place-merges.index') }}" class="{{ $cardClass }}">
                        <div class="font-semibold">{{ __('admin.overview.merge_places') }}</div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.merge_places_help') }}</div>
                    </a>
                @endif

                @if ($canModeratePhotos)
                    <a href="{{ route('admin.photos.library') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.photo_library') }}</div>
                            <span class="{{ $neutralBadge }}">{{ __('admin.overview.total_count', ['count' => $photoCount]) }}</span>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.photo_library_help') }}</div>
                    </a>
                @endif

                @if ($canManageFeatures)
                    <a href="{{ route('admin.features.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.features') }}</div>
                            <span class="{{ $neutralBadge }}">{{ __('admin.overview.active_count', ['count' => $featureCount]) }}</span>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.features_help') }}</div>
                    </a>
                @endif

                @if ($canViewImports)
                    <a href="{{ route('admin.imports.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.imports') }}</div>
                            <div class="flex flex-wrap justify-end gap-1">
                                @if ($newImportCandidateCount > 0)
                                    <span class="{{ $openBadge }}">{{ __('admin.overview.import_candidates_count', ['count' => $newImportCandidateCount]) }}</span>
                                @endif
                                @if ($pendingImportReviewCount > 0)
                                    <span class="{{ $openBadge }}">{{ __('admin.overview.import_reviews_count', ['count' => $pendingImportReviewCount]) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.imports_help') }}</div>
                    </a>
                @endif

                @if ($canSendSystemNotifications)
                    <a href="{{ route('admin.notifications.create') }}" class="{{ $cardClass }}">
                        <div class="font-semibold">{{ __('admin.overview.system_notification') }}</div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.system_notification_help') }}</div>
                    </a>
                @endif

                @if ($canUseSystemTools)
                    <a href="{{ route('admin.system.index') }}" class="{{ $cardClass }}">
                        <div class="font-semibold">{{ __('admin.overview.system_tools') }}</div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.system_tools_help') }}</div>
                    </a>
                @endif
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('admin.overview.sections.moderation') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.overview.sections.moderation_help') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @if ($canApproveChanges)
                    <a href="{{ route('admin.change-requests.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.change_requests') }}</div>
                            <div class="flex flex-wrap justify-end gap-1">
                                @if ($pendingChangeRequestCount > 0)
                                    <span class="{{ $openBadge }}">{{ __('admin.overview.open_count', ['count' => $pendingChangeRequestCount]) }}</span>
                                @endif
                                @if ($quarantinedPlaceSubmissionCount > 0)
                                    <span class="{{ $openBadge }}">{{ __('admin.overview.quarantine_count', ['count' => $quarantinedPlaceSubmissionCount]) }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.change_requests_help') }}</div>
                    </a>
                @endif

                @if ($canModeratePhotos)
                    <a href="{{ route('admin.photos.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.photo_moderation') }}</div>
                            @if (($pendingPhotoCount + $pendingPhotoReportCount) > 0)
                                <span class="{{ $openBadge }}">{{ __('admin.overview.open_count', ['count' => $pendingPhotoCount + $pendingPhotoReportCount]) }}</span>
                            @endif
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.photo_moderation_help') }}</div>
                    </a>
                @endif

                @if ($canViewReviewReports)
                    <a href="{{ route('admin.review-reports.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.review_reports') }}</div>
                            @if ($pendingReviewReportCount > 0)
                                <span class="{{ $openBadge }}">{{ __('admin.overview.open_count', ['count' => $pendingReviewReportCount]) }}</span>
                            @endif
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.review_reports_help') }}</div>
                    </a>
                @endif

                @if ($canManageSupport)
                    <a href="{{ route('admin.support.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.support') }}</div>
                            <div class="flex max-w-[60%] flex-wrap justify-end gap-1">
                                @foreach ($supportStatusCounts as $status => $count)
                                    @continue($count <= 0)
                                    <span class="{{ in_array($status, ['resolved', 'closed'], true) ? $doneBadge : $openBadge }}">
                                        {{ $count }} {{ __('admin.support.statuses.'.$status) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.support_help') }}</div>
                    </a>
                @endif
            </div>
        </section>

        <section class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('admin.overview.sections.information') }}</flux:heading>
                <flux:text class="mt-1">{{ __('admin.overview.sections.information_help') }}</flux:text>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                @if ($canViewAuditLogs)
                    <a href="{{ route('admin.audit-logs.index') }}" class="{{ $cardClass }}">
                        <div class="flex items-start justify-between gap-3">
                            <div class="font-semibold">{{ __('admin.overview.audit_log') }}</div>
                            <span class="{{ $neutralBadge }}">{{ __('admin.overview.total_count', ['count' => $auditLogCount]) }}</span>
                        </div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.audit_log_help') }}</div>
                    </a>
                @endif

                @if ($canViewStatistics)
                    <a href="{{ route('admin.statistics.index') }}" class="{{ $cardClass }}">
                        <div class="font-semibold">{{ __('admin.overview.statistics') }}</div>
                        <div class="mt-1 text-sm text-neutral-500">{{ __('admin.overview.statistics_help') }}</div>
                    </a>
                @endif
            </div>
        </section>
    </div>
</x-layouts::app>
