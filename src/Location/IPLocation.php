<?php
namespace JiaLeo\Laravel\Location;

/**
 * 手机归属地查询
 * Class MobileLocation
 * @package JiaLeo\Laravel\MobileLocation
 */
class IPLocation
{

    /**
     * 淘宝IP库查询(每秒10IOPS限制)
     * @param $ip
     * @return bool|mixed|string
     */
    public function taobaoQuery($ip)
    {
        load_helper('Network');
        $res = http_get('http://ip.taobao.com/service/getIpInfo.php?ip=' . $ip);

        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            return false;
        }

        return $res;
    }

    /**
     * 淘宝IP库查询2(发现还是有IOPS限制)
     * @param $ip
     * @return bool|mixed|string
     */
    public function taobaoQuery2($ip)
    {
        load_helper('Network');
        $res = http_post('http://ip.taobao.com/service/getIpInfo2.php', ['ip' => $ip]);
        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (!$res || !isset($res['code']) || $res['code'] != 0) {
            return false;
        }

        return $res;
    }

    /**
     * 新浪IP库查询(暂无发现IOPS限制)
     * @param $ip
     * @return bool|mixed|string
     */
    public function sinaQuery($ip)
    {
        load_helper('Network');
        $res = http_get('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=' . $ip);
        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (!$res || !isset($res['ret']) || $res['ret'] != 1) {
            return false;
        }

        return $res;
    }

    /**
     * 百度IP库查询
     * 接口文档:http://lbsyun.baidu.com/index.php?title=webapi/ip-api
     * 需自行申请百度账号开发者获取ak参数
     * IOSP限制根据文档所示
     * @param $ip
     * @return bool|mixed|string
     */
    public function baiduQuery($ip, $ak)
    {
        load_helper('Network');
        $res = http_get('https://api.map.baidu.com/location/ip?ak=' . $ak . '&coor=bd09ll&ip=' . $ip);
        if (!$res) {
            return false;
        }

        $res = json_decode($res, true);
        if (!$res || !isset($res['status']) || $res['status'] != 0) {
            return false;
        }

        return $res;
    }

    /**
     * 太平洋IP库查询(暂无发现IOPS限制)
     * http://whois.pconline.com.cn
     * @param $ip
     * @return bool|mixed|string
     */
    public function pconlineQuery($ip)
    {
        load_helper('Network');
        $res = http_get('http://whois.pconline.com.cn/ipJson.jsp?json=true&ip=' . $ip);
        if (!$res) {
            return false;
        }
        preg_match('/\{(.+?)\}/',$res,$res);

        if(!isset($res[0])){
            return false;
        }

        $res = iconv('gbk', 'utf-8', $res[0]);
        $res = json_decode($res, true);
        if (!$res || !isset($res['err']) || $res['err'] != '') {
            return false;
        }

        return $res;
    }


    //TODO  京东查询IP接口

}