<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\Stripe\StripeServiceInterface;
use App\Services\Stripe\StripeService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * アプリケーションのサービスを登録する。
     */
    public function register(): void
    {
        $this->app->bind(
            StripeServiceInterface::class,
            StripeService::class,
        );
    }

    /**
     * アプリケーションのサービスを起動する。
     */
    public function boot(): void
    {
        //
    }
}
