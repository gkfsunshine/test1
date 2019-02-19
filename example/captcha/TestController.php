<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Exceptions\ApiException;

class TestController extends Controller
{

    /**
     * 渲染验证码到浏览器
     */
    public function captcha(){
        \Captcha::setLimit(4)->create('login_code',1);
    }

    /**
     * 验证验证码
     */
    public function check(){
        $result=\Captcha::checkCodeInfo('login_code', 'tiiu');
        dd($result);
    }

}
