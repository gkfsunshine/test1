<?php
namespace JiaLeo\Laravel\Location;

/**
 * 手机归属地查询
 * Class MobileLocation
 * @package JiaLeo\Laravel\MobileLocation
 */
class MobileLocation
{
    /**
     * 淘宝查询接口
     * @param int $mobile 手机号
     * @return bool|mixed|string
     */
    public function query($mobile)
    {
        load_helper('Network');
        $res = http_get('https://tcc.taobao.com/cc/json/mobile_tel_segment.htm?tel=' . $mobile);
        $res = trim(explode('=', $res)[1]);
        $res = iconv('gbk', 'utf-8', $res);
        $res = str_replace("'", '"', $res);
        $res = preg_replace('/(\w+):/is', '"$1":', $res);
        $res = json_decode($res, true);

        unset($res['areaVid'], $res['ispVid']);

        return $res;
    }
}