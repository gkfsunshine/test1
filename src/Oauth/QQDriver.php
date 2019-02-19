<?php


namespace JiaLeo\Laravel\Oauth;


use App\Exceptions\ApiException;

/**
 * Class QQDriver
 * @package JiaLeo\Laravel\Oauth
 */
class QQDriver implements OauthInterface
{

    public $appid;
    public $appkey;
    public $redirect_uri;

    public $error_code;
    public $error_msg;

    /** 获取授权url
     * @param $redirect_uri
     * @param $state
     * @return string
     */
    public function getAuthUrl($redirect_uri, $state)
    {
        //拼接URL
        $dialog_url = "https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id="
            . $this->appid . "&redirect_uri=" . urlencode($redirect_uri) . "&state="
            . $state;

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

        //拼接URL
        $token_url = "https://graph.qq.com/oauth2.0/token?grant_type=authorization_code&"
            . "client_id=" . $this->appid . "&redirect_uri=" . urlencode($redirect_uri)
            . "&client_secret=" . $this->appkey . "&code=" . $code;

        load_helper('Network');
        $access_info = http_get($token_url);

        if (!$access_info) {
            throw new ApiException('获取用户信息错误!');
        }

        //判断返回结果
        if (strpos($access_info, "callback") !== false) {
            $lpos = strpos($access_info, "(");
            $rpos = strrpos($access_info, ")");
            $response = substr($access_info, $lpos + 1, $rpos - $lpos - 1);

            $msg = json_decode($response);
            if (isset($msg->error)) {
                $this->error_code = $msg->error;
                $this->error_msg = $msg->error_description;

                throw new ApiException('获取用户信息错误!错误代码:' . $this->error_code . ' 错误信息:' . $this->error_msg);
            }
        }


        $params = array();
        parse_str($access_info, $params);
        $access_token = $params['access_token'];

        //Step3：使用Access Token来获取用户的OpenID
        $graph_url = "https://graph.qq.com/oauth2.0/me?access_token=" . $params['access_token'];
        $str = http_get($graph_url);
        if (strpos($str, "callback") !== false) {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str = substr($str, $lpos + 1, $rpos - $lpos - 1);
        }
        $user = json_decode($str, true);
        if (isset($user['error'])) {

            $this->error_code = $user['error'];
            $this->error_msg = $user['error_description'];

            throw new ApiException('获取用户信息错误!错误代码:' . $this->error_code . ' 错误信息:' . $this->error_msg);
        }

        return array(
            'openid' => $user['openid'],
            'access_token' => $access_token,
            'expires_in' => time() + $params['expires_in'],
            'refresh_token' => $params['refresh_token']
        );
    }

    /**
     * 获取用户信息
     * @param $uid
     * @param $access_token
     * @return array
     * @throws ApiException
     */
    public function getUserInfo($openid, $access_token)
    {
        load_helper('Network');
        $url = 'https://graph.qq.com/user/get_user_info?access_token=' . $access_token . '&oauth_consumer_key=' . $this->appid . '&openid=' . $openid;
        $str = http_get($url);
        if (!$str) {
            $this->error_msg = '请求失败!';

            throw new ApiException('获取用户信息错误!错误代码:' . $this->error_code . ' 错误信息:' . $this->error_msg);
        }

        $user = json_decode($str, true);
        if (!$user || (isset($user['ret']) && $user['ret'] != 0)) {

            $this->error_code = $user['ret'];
            $this->error_msg = $user['msg'];

            throw new ApiException('获取用户信息错误!错误代码:' . $this->error_code . ' 错误信息:' . $this->error_msg);
        }
        return array(
            'nickname' => $user['nickname'],
            'headimg' => $user['figureurl_2'],
            'openid' => $openid,
            'province' => $user['province'],
            'city' => $user['city'],
            'sex' => $this->tranSex($user['gender'])
        );
    }


    /**
     * 根据access_token获取unionid
     * @param $access_token
     * @return mixed
     * @throws ApiException
     */
    public function getUnionidByAccessToken($access_token){
        $url ="https://graph.qq.com/oauth2.0/me?access_token=$access_token&unionid=1";
        load_helper('Network');
        $str =http_get($url);
        if (!$str) {

            throw new ApiException('获取用unionID息错误');
        }
        if (strpos($str, "callback") !== false) {
            $lpos = strpos($str, "(");
            $rpos = strrpos($str, ")");
            $str = substr($str, $lpos + 1, $rpos - $lpos - 1);
        }
        $unionid_info = json_decode($str,true);
        if (!$unionid_info || (isset($unionid_info['ret']) && $unionid_info['ret'] != 0)) {

            $this->error_code = $unionid_info['ret'];
            $this->error_msg = $unionid_info['msg'];

            throw new ApiException('获取用户信息错误!错误代码:' . $this->error_code . ' 错误信息:' . $this->error_msg);
        }
        return $unionid_info;
    }

    /**
     * 转换性别
     * @param $str
     * @return int
     */
    private function tranSex($str)
    {

        switch ($str) {
            case '男' :
                $return_str = 1;
                break;
            case '女' :
                $return_str = 2;
                break;
            default :
                $return_str = 0;
        }
        return $return_str;
    }
}