<?php


namespace JiaLeo\Laravel\Oauth;


use App\Exceptions\ApiException;

class WeiboDriver
{

    public $appid;
    public $appkey;

    public $error_code;
    public $error_msg;

    public $http_content;


    /**
     * 获取跳转授权url
     * @param $redirect_uri
     * @return string
     */
    public function getAuthUrl($redirect_uri, $state)
    {
        //Step1：获取Authorization Code
        $dialog_url = 'https://api.weibo.com/oauth2/authorize?client_id=' . $this->appid . '&response_type=code'
            . '&redirect_uri=' . urlencode($redirect_uri) . '&state=' . $state;
        return $dialog_url;
    }


    /**
     * 使用code获取openid
     * @param $redirect_uri
     * @param $code
     * @return array
     * @throws ApiException
     */
    public function getOpenidByCode($redirect_uri, $code)
    {

        //Step2：通过Authorization Code获取Access Token
        $token_url = 'https://api.weibo.com/oauth2/access_token';

        $data = array(
            'client_id' => $this->appid,
            'client_secret' => $this->appkey,
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri
        );

        $url = $token_url . '?' . http_build_query($data);

        $access_info = $this->http_post($url, array());

        if (!$access_info) {
            $error = $this->http_content;
            if ($error) {
                $error_info = json_decode($error, true);
                if ($error_info) {
                    $this->error_code = $error_info['error_code'];
                    $this->error_msg = $error_info['error_description'];

                    throw new ApiException('获取用户信息错误!错误代码:' . $this->error_code . ' 错误信息:' . $this->error_msg);
                }
            }

            throw new ApiException('获取用户信息错误!');
        }

        $access_info = json_decode($access_info, true);
        $access_token = $access_info['access_token'];

        //Step3：使用Access Token来获取用户的uid
        $graph_url = "https://api.weibo.com/oauth2/get_token_info?access_token=" . $access_token;
        $str = $this->http_post($graph_url, array());
        if (!$str) {
            throw new ApiException('获取用户信息错误!');
        }

        $uid_info = json_decode($str, true);

        return array(
            'openid' => $uid_info['uid'],
            'access_token' => $access_token,
            'expires_in' => $access_info['expires_in'],
            'refresh_token' => ''
        );
    }

    /**
     * 获取用户信息
     * @param $uid
     * @param $access_token
     * @return array
     * @throws ApiException
     */
    public function getUserInfo($uid, $access_token)
    {

        $user_url = 'https://api.weibo.com/2/users/show.json?access_token=' . $access_token . '&uid=' . $uid;
        $user = $this->http_get($user_url);
        if (!$user) {
            throw new ApiException('获取用户信息错误!');
        }

        $uid_info = json_decode($user, true);
        return array(
            'nickname' => $uid_info['name'],
            'headimg' => $uid_info['avatar_large'],
            'openid' => $uid_info['id'],
            'province' => $uid_info['province'],
            'city' => $uid_info['city'],
            'sex' => $this->tranSex($uid_info['gender'])
        );
    }

    /**
     * POST 请求
     * @param string $url
     * @param array $param
     * @param bool $post_file 是否传送文件,true的时候param为file类型(列如:$_FILE['file1'])
     * @param int $timeout 超时时间(秒)
     * @return string content
     */
    public function http_post($url, $param, $post_file = false, $timeout = 0)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, false);
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
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);

        if (!empty($timeout)) {
            curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeout);   //秒
        }

        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Keep-Alive: 300";
        $header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
        $header[] = "Accept-Language: en-us,en;q=0.5";
        $header[] = "Pragma: "; // browsers keep this blank.

        curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);

        $user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        curl_setopt($oCurl, CURLOPT_USERAGENT, $user_agent);

        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);

        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            $this->http_content = $sContent;
            return false;
        }
    }

    /**
     * GET 请求
     * @param string $url
     * @param string $timeout 超时时间(秒)
     */
    public function http_get($url, $timeout = 0)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);

        $user_agent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
        curl_setopt($oCurl, CURLOPT_USERAGENT, $user_agent);

        if (!empty($timeout)) {
            curl_setopt($oCurl, CURLOPT_TIMEOUT, $timeout);   //秒
        }

        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            $this->http_content = $sContent;
            return false;
        }
    }

    /**
     * 转换性别
     * @param $str
     * @return int
     */
    private function tranSex($str)
    {

        switch ($str) {
            case 'g' :
                $return_str = 1;
                break;
            case 'm' :
                $return_str = 2;
                break;
            default :
                $return_str = 0;
        }
        return $return_str;
    }
}