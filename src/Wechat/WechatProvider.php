<?php

namespace JiaLeo\Laravel\Wechat;

use Illuminate\Support\ServiceProvider;

class WechatProvider extends ServiceProvider
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
        $this->app->singleton('wechat', function () {
            return new Wechat(config('wechat.default'));
        });

    }

    /**
     * 获取由提供者提供的服务.
     *
     * @return array
     */
    public function provides()
    {
        return ['wechat'];
    }
}
