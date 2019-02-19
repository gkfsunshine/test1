<?php

namespace JiaLeo\Laravel\Sentry;

use Illuminate\Support\ServiceProvider;

class SentryProvider extends ServiceProvider
{

    protected $defer = true;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('sentry', function ($app) {
            $user_config = config('sentry');
            $base_path = base_path();
            $client = \Sentry\SentryLaravel\SentryLaravel::getClient(array_merge(array(
                'environment' => $app->environment(),
                'prefixes' => array($base_path),
                'app_path' => $base_path,
                'excluded_app_paths' => array($base_path . '/vendor'),
            ), $user_config));

            return $client;
        });
    }

    /**
     * 获取由提供者提供的服务.
     *
     * @return array
     */
    public function provides()
    {
        return ['sentry'];
    }
}
