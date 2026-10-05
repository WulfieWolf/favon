<?php

namespace App\Providers;

use App\Models\User;
use App\Services\PermissionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();

        Gate::define('viewPulse', function (User $user): bool {
            return app(PermissionService::class)->can($user, 'admin.access');
        });

        User::created(function (User $user): void {
            $roleId = DB::table('roles')
                ->where('slug', 'user')
                ->where('is_active', true)
                ->value('id');

            if ($roleId) {
                DB::table('user_roles')->updateOrInsert(
                    ['user_id' => $user->id, 'role_id' => $roleId],
                    [
                        'assigned_by' => null,
                        'assigned_at' => now(),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
        });
    }

    private function configureRateLimiting(): void
    {
        $actorKey = static fn (Request $request): string => $request->user()
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'ip:'.$request->ip();

        $isPrivileged = static function (Request $request): bool {
            $user = $request->user();

            if (! $user) {
                return false;
            }

            return app(PermissionService::class)->can($user, 'admin.access');
        };

        RateLimiter::for('support-submit', function (Request $request) use ($actorKey, $isPrivileged): array|Limit {
            if ($isPrivileged($request)) {
                return Limit::none();
            }

            $key = $actorKey($request);

            return [
                Limit::perMinute(2)->by('support-submit-minute:'.$key),
                Limit::perHour(5)->by('support-submit-hour:'.$key),
                Limit::perDay(15)->by('support-submit-day:'.$key),
            ];
        });

        RateLimiter::for('place-create', function (Request $request) use ($actorKey, $isPrivileged): array|Limit {
            if ($isPrivileged($request)) {
                return Limit::none();
            }

            $key = $actorKey($request);
            $newAccount = $request->user()?->created_at?->gt(now()->subDay()) ?? false;

            return [
                Limit::perMinute(2)->by('place-create-minute:'.$key),
                Limit::perHour($newAccount ? 3 : 5)->by('place-create-hour:'.$key),
                Limit::perDay($newAccount ? 8 : 15)->by('place-create-day:'.$key),
                Limit::perDay(30)->by('place-create-ip-day:'.$request->ip()),
            ];
        });

        RateLimiter::for('community-write', function (Request $request) use ($actorKey, $isPrivileged): array|Limit {
            if ($isPrivileged($request)) {
                return Limit::none();
            }

            $key = $actorKey($request);

            return [
                Limit::perMinute(20)->by('community-write-minute:'.$key),
                Limit::perHour(100)->by('community-write-hour:'.$key),
            ];
        });





        RateLimiter::for('report-create', function (Request $request) use ($actorKey, $isPrivileged): array|Limit {
            if ($isPrivileged($request)) {
                return Limit::none();
            }

            $key = $actorKey($request);

            return [
                Limit::perMinute(3)->by('report-create-minute:'.$key),
                Limit::perHour(10)->by('report-create-hour:'.$key),
                Limit::perDay(30)->by('report-create-day:'.$key),
            ];
        });

        RateLimiter::for('support-reply', function (Request $request) use ($actorKey, $isPrivileged): array|Limit {
            if ($isPrivileged($request)) {
                return Limit::none();
            }

            return Limit::perHour(20)->by('support-reply-hour:'.$actorKey($request));
        });

        RateLimiter::for('engagement-write', function (Request $request) use ($actorKey, $isPrivileged): array|Limit {
            if ($isPrivileged($request)) {
                return Limit::none();
            }

            return [
                Limit::perMinute(60)->by('engagement-write-minute:'.$actorKey($request)),
                Limit::perHour(300)->by('engagement-write-hour:'.$actorKey($request)),
            ];
        });

        RateLimiter::for('public-read', function (Request $request): Limit {
            if (! config('camperwolf.security.public_read_limit_enabled', false)) {
                return Limit::none();
            }

            return Limit::perMinute(
                (int) config('camperwolf.security.public_read_per_minute', 180),
            )->by('public-read:'.$request->ip());
        });

    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
