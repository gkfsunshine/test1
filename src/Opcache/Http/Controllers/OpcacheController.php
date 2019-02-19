<?php

namespace JiaLeo\Laravel\Opcache\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use JiaLeo\Laravel\Opcache\OpcacheClass;

use Illuminate\Support\Facades\Crypt as Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class OpcacheController.
 */
class OpcacheController extends BaseController
{
    public function __construct()
    {
        //$this->checkAuth();
    }

    /**
     * Clear the OPcache.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function clear()
    {
        return response(['result' => OpcacheClass::clear()]);
    }

    /**
     * Get config values.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function config()
    {
        return response(['result' => OpcacheClass::getConfig()]);
    }

    /**
     * Get status info.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function status()
    {
        return response(['result' => OpcacheClass::getStatus()]);
    }

    /**
     * Optimize.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function optimize()
    {
        return response(['result' => OpcacheClass::optimize()]);
    }

    /**
     * 验证key
     * @return bool
     */
    public function checkAuth()
    {

        try {
            $decrypted = Crypt::decrypt(request()->query->get('key'));
        } catch (DecryptException $e) {
            $decrypted = '';
        }

        if ($decrypted == 'opcache') {
            return true;
        }

//        if (in_array($this->getRequestIp(request()), [$this->getServerIp(), '127.0.0.1', '::1'])) {
//            return true;
//        }

        throw new HttpException(403, 'This action is unauthorized.');
    }

    /**
     * Get ip from different request headers.
     * @param $request
     * @return mixed
     */
    protected function getRequestIp($request)
    {
        if ($request->server('HTTP_CF_CONNECTING_IP')) {
            // cloudflare
            return $request->server('HTTP_CF_CONNECTING_IP');
        }

        if ($request->server('X_FORWARDED_FOR')) {
            // forwarded proxy
            return $request->server('X_FORWARDED_FOR');
        }

        if ($request->server('REMOTE_ADDR')) {
            // remote header
            return $request->server('REMOTE_ADDR');
        }
    }

    /**
     * Get the server ip.
     *
     * @return string
     */
    protected function getServerIp()
    {
        if (isset($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }

        if (isset($_SERVER['LOCAL_ADDR'])) {
            return $_SERVER['LOCAL_ADDR'];
        }

        return '127.0.0.1';
    }
}
