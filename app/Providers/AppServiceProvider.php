<?php

namespace App\Providers;

use App\Models\Business;
use App\Models\Category;
use App\Models\Image;
use App\Models\Integration;
use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Outlet;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Models\Voucher;
use App\Models\VoucherOffer;
use App\Support\ActivityLog\AuditTrail;
use App\Support\Api\ApiErrors;
use App\Support\Workspace;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Sanctum\Sanctum;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Workspace::class);
        $this->app->scoped(AuditTrail::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configurePartnerApi();
    }

    /**
     * A partner API key works only while its integration is usable, and each integration has its own rate limit.
     */
    protected function configurePartnerApi(): void
    {
        Sanctum::authenticateAccessTokensUsing(
            fn (PersonalAccessToken $token, bool $isValid): bool => $isValid
                && $token->tokenable instanceof Integration
                && $token->tokenable->isUsable(),
        );

        Scramble::afterOpenApiGenerated(fn (OpenApi $openApi) => ApiErrors::document($openApi));

        RateLimiter::for('partner-api', fn (Request $request): Limit => Limit::perMinute(300)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('counter', fn (Request $request): Limit => Limit::perMinute(60)
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
    }

    /**
     * System admins reach the admin area only. Inside a business they need a membership like anyone else.
     */
    protected function configureAuthorization(): void
    {
        Gate::define('admin', fn (User $user): bool => $user->is_admin && ! $user->isSuspended());

        // The partner API docs at /docs/api. Scramble opens them to everyone only in the local environment.
        Gate::define('viewApiDocs', fn (User $user): bool => Gate::forUser($user)->allows('admin'));
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Relation::enforceMorphMap([
            'user' => User::class,
            'business' => Business::class,
            'outlet' => Outlet::class,
            'membership' => Membership::class,
            'invitation' => Invitation::class,
            'category' => Category::class,
            'tag' => Tag::class,
            'setting' => Setting::class,
            'image' => Image::class,
            'integration' => Integration::class,
            'voucher_offer' => VoucherOffer::class,
            'voucher' => Voucher::class,
        ]);

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
