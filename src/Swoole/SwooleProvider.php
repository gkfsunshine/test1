<?php
namespace JiaLeo\Laravel\Swoole;

use Illuminate\Support\ServiceProvider;

class SwooleProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //注册自动生成命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                'JiaLeo\Laravel\Swoole\Console\CreateSwoole',
                'JiaLeo\Laravel\Swoole\Console\Register',
                'JiaLeo\Laravel\Swoole\Console\Gateway',
                'JiaLeo\Laravel\Swoole\Console\Worker',
                'JiaLeo\Laravel\Swoole\Console\Swoole',
            ]);
        }
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}