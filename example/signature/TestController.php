<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use JiaLeo\Laravel\Signature\CacheStorage;
use JiaLeo\Laravel\Signature\Mysql;
use JiaLeo\Laravel\Signature\MysqlStorage;
use JiaLeo\Laravel\Signature\RedisStorage;

class TestController extends Controller
{

    public function test(){

        //检测签名是否正确
        \Signature::checkSign();
    }

}
