<?php

namespace TrackAnyDevice\SmsGateway;

use Illuminate\Support\ServiceProvider;
use TrackAnyDevice\SmsGateway\Contracts\SmsGatewayContract;

class SmsGatewayServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sms.php', 'sms');

        $this->app->singleton(SmsGatewayContract::class, SmsGatewayService::class);
        $this->app->alias(SmsGatewayContract::class, SmsGatewayService::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sms.php' => config_path('sms.php'),
            ], 'sms-gateway-config');
        }
    }
}
