<?php
/**
 * Created by PhpStorm.
 * User: yuelin
 * Date: 2018/4/26
 * Time: 下午3:24
 */

namespace JiaLeo\Laravel\Oauth;


interface OauthInterface
{

    public function getAuthUrl($redirect_uri, $state);


    public function getOpenidByCode($redirect_uri, $code);


    public function getUserInfo($openid, $access_token);
}