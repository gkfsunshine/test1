<?php

namespace JiaLeo\Laravel\Captcha;

use Illuminate\Support\ServiceProvider;

class CaptchaProvider extends ServiceProvider
{

    protected $defer = true;

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
        $this->app->singleton('captcha', function () {
            return new Captcha;
        });

    }

    /**
     * 获取由提供者提供的服务.
     *
     * @return array
     */
    public function provides()
    {
        return ['captcha'];
    }
}
