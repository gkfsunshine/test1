<?php
namespace JiaLeo\Laravel\Wechat;

/**
 *  微信公众平台PHP-SDK
 * @author liang
 */


class Wechat extends WechatOrigin
{
    private $config;
    private $type;

    public function __construct($type = 'mp')
    {
        $config = config('wechat');

        if (config('wechat.origin') == 'file') {      //使用文件中的配置
            $options = $config[$type];
        } else {        //使用数据库中的缓存
            //先用缓存中获取
            $options = \Cache::get('wechat:config:' . $type);

            if (empty($options)) {
                $get_config = \App\Logic\Admin\WechatConfigLogic::getOneWechatConfig($type);
                $options = $get_config->toArray();

                \Cache::put('wechat:config:' . $type, serialize($options), 60 * 60 * 12);
            } else {
                $options = unserialize($options);
            }
        }

        $this->config = $options;
        $this->type = $type;

        parent::__construct($options);
    }

    /**
     * 重载设置缓存
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename, $value, $expired)
    {
        return \Cache::put($cachename, $value, $expired / 60);
    }

    /**
     * 重载获取缓存
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename)
    {
        return \Cache::get($cachename);
    }

    /**
     * 重载清除缓存
     * @param string $cachename
     * @return boolean
     */
    protected function removeCache($cachename)
    {
        return \Cache::forget($cachename);
    }

    /**
     * POST 请求(重载)
     * @param string $url
     * @param array $param
     * @param boolean $post_file 是否文件上传
     * @return string content
     */
    public function http_post($url, $param, $post_file = false)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        if (is_string($param) || $post_file) {
            $strPOST = $param;
        } else {
            $aPOST = array();
            foreach ($param as $key => $val) {
                $aPOST[] = $key . "=" . urlencode($val);
            }
            $strPOST = join("&", $aPOST);
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($oCurl, CURLOPT_POST, true);

        /*curl_setopt($oCurl, CURLOPT_HTTPPROXYTUNNEL, TRUE);
        curl_setopt($oCurl, CURLOPT_PROXY, '183.48.73.135:2233');
        curl_setopt($oCurl, CURLOPT_PROXYUSERPWD, 'user:password');//如果要密码的话，加上这个*/

        //php版本大于5.5时
        if ($post_file && version_compare(PHP_VERSION, '5.5.0') >= 0) {
            foreach ($strPOST as $key => $v) {
                $args[$key] = new \CurlFile($strPOST[$key], mime_content_type($strPOST[$key]));
                curl_setopt($oCurl, CURLOPT_POSTFIELDS, $args);
            }
        } else {
            curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
        }

        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }

    /**
     * 获取配置信息
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errMsg;
    }


    /**
     * 获取错误代码
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errCode;
    }

    /**
     * 设置access_token
     * @return string
     */
    public function setAccessToken($value)
    {
        $this->access_token = $value;
        return $this;
    }

    /**
     * 清除access_token
     * @return string
     */
    public function clearAccessToken()
    {
        $this->access_token = '';
    }

    /**
     * oauth 授权跳转接口
     * @param string $callback 回调URI
     * @return string
     */
    public function getOauthRedirect($callback, $state = '', $scope = 'snsapi_userinfo')
    {
        $qiye = '';
        if (isset($this->agentid)){
            $qiye = '&agentid='.$this->agentid;
        }
        return self::OAUTH_PREFIX . self::OAUTH_AUTHORIZE_URL . 'appid=' . $this->appid . '&redirect_uri=' . urlencode($callback) . '&response_type=code&scope=' . $scope .$qiye. '&state=' . $state .'#wechat_redirect';
    }
}
