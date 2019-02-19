<?php

namespace JiaLeo\Laravel\Sms;

use App\Exceptions\ApiException;
use Illuminate\Support\ServiceProvider;

class SmsProvider extends ServiceProvider
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
        $this->app->singleton('sms', function () {
            $config = config('sms');
            $store = $config['log_save'];
            $store_class = 'JiaLeo\Laravel\Sms\\' . ucfirst($store) . 'Store';

            if (!class_exists($store_class)) {
                throw new ApiException("Sms Store [$store] is not supported.");
            }

            //短信模板驱动
            if (isset($config['sms_template']) && $config['sms_template'] != 'file') {
                $sms_template = $config['sms_template'];
                $template_class = 'JiaLeo\Laravel\Sms\\' . ucfirst($sms_template) . 'Template';

                if (!class_exists($template_class)) {
                    throw new ApiException("Sms Template [$sms_template] is not supported.");
                }
                $config['templet'] = $template_class::getTemplate();
            }

            $store_class = new $store_class();

            return new SmsManager($config, $store_class);
        });
    }

    /**
     * 获取由提供者提供的服务.
     *
     * @return array
     */
    public function provides()
    {
        return ['sms'];
    }
}
