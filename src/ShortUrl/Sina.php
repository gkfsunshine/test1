<?php
namespace JiaLeo\Laravel\ShortUrl;


/**
 * 转换为新浪的短网址(t.cn)
 * Class Sina
 * @package JiaLeo\Laravel\ShortUrl
 */
class Sina
{

    /**
     * 转换
     * @param $url
     * @return bool|mixed|string
     */
    public static function transform($url){
        load_helper('Network');

        $query_url = 'http://api.t.sina.com.cn/short_url/shorten.json?url_long='.$url.'&source=3271760578';
        $res = http_get($query_url);
        return $res;
    }
}