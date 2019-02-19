<?php

namespace JiaLeo\Laravel\Signature;

use App\Exceptions\ApiException;
use Illuminate\Support\ServiceProvider;

class SignatureProvider extends ServiceProvider
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
        $this->app->singleton('signature', function () {
            $config = config('signature');
            if (empty($config)) {
                throw new ApiException('signature配置文件不存在');
            }

            $store = $config['store_drive'];
            $store_class = 'JiaLeo\Laravel\Signature\\' . ucfirst($store) . 'Storage';
            if (!class_exists($store_class)) {
                throw new ApiException("Signature Store [$store] is not supported.");
            }

            $store_class = new $store_class();
            return new Signature($store_class, $config);
        });

    }

    /**
     * 获取由提供者提供的服务.
     *
     * @return array
     */
    public function provides()
    {
        return ['signature'];
    }
}
