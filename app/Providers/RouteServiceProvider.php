<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters,
     * and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            /*
             * Stripe Webhookは、通常APIとは別の制限枠にする。
             *
             * Stripeによる再送や複数イベントの集中を考慮しつつ、
             * 異常な大量アクセスを無制限には受け付けない。
             */
            if ($request->is('api/webhooks/stripe')) {
                return Limit::perMinute(300)
                    ->by('stripe-webhook:'.$request->ip());
            }

            /*
             * 既存APIのレート制限は変更しない。
             *
             * 認証済みの場合はユーザー単位、
             * 未認証の場合はIPアドレス単位で制限する。
             */
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
