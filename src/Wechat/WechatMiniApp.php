<?php
namespace JiaLeo\Laravel\Wechat;

use App\Exceptions\ApiException;

/**
 * 小程序
 * Class WechatMiniApp
 * @package JiaLeo\Laravel\Wechat
 */
class WechatMiniApp
{
    private $appid;
    private $appsecret;
    public $access_token;

    public $errCode = 0;
    public $errMsg = 'ok';
    public $auth_type = 2;

    const API_URL_PREFIX = 'https://api.weixin.qq.com/cgi-bin';
    const AUTH_URL = '/token?grant_type=client_credential&';

    const TEMPLATE_SEND_URL = '/message/wxopen/template/send?';                         //小程序模板消息
    const SET_DNS_URL = 'https://api.weixin.qq.com/wxa/modify_domain?';                 //设置小程序服务器域名
    const SET_WEB_VIEM_DOMAIN_URL = 'https://api.weixin.qq.com/wxa/setwebviewdomain?';  //设置小程序服务器域名
    const UPLOAD_URL = 'https://api.weixin.qq.com/wxa/commit?';                         //上传小程序
    const QRCODE_URL = 'https://api.weixin.qq.com/wxa/get_qrcode?';                     //小程序体验二维码
    const CATEGORY_URL = 'https://api.weixin.qq.com/wxa/get_category?';                 //小程序账号类目
    const GET_TESTER_LIST_URL = 'https://api.weixin.qq.com/wxa/memberauth?';            //获取体验者列表
    const BIND_TESTER_URL = 'https://api.weixin.qq.com/wxa/bind_tester?';               //绑定微信用户为小程序体验者
    const UNBIND_TESTER_URL = 'https://api.weixin.qq.com/wxa/unbind_tester?';           //解除绑定小程序的体验者
    const GET_PAGE_URL = 'https://api.weixin.qq.com/wxa/get_page?';                     //小程序的页面配置
    const CHECK_URL = 'https://api.weixin.qq.com/wxa/submit_audit?';                    //小程序提交审核
    const AUDITSTATUS_URL = 'https://api.weixin.qq.com/wxa/get_auditstatus?';           //小程序提交审核
    const UNDOCODEAUDIT_URL = 'https://api.weixin.qq.com/wxa/undocodeaudit?';           //小程序审核撤回
    const RELEASE_URL = 'https://api.weixin.qq.com/wxa/release?';                       //发布小程序
    const WXCODEUNLIMIT_URL = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?';       //获取小程序码，适用于需要的码数量极多的业务场景。
    const WXACODE_URL = 'https://api.weixin.qq.com/wxa/getwxacode?';                    //获取小程序码，适用于需要的码数量较少的业务场景
    const WXAQRCODE_URL = 'https://api.weixin.qq.com/cgi-bin/wxaapp/createwxaqrcode?';  //获取小程序二维码，适用于需要的码数量较少的业务场景


    /**
     * 构造函数
     * @param $type string 用户在小程序登录后获取的会话密钥
     */
    public function __construct($type = 'miniapp')
    {
        $config = Config('wechat.' . $type);

        $this->appid = $config['appid'];
        $this->appsecret = $config['appsecret'];
    }

    /**
     * js code获取session
     * @param $code
     * @return bool|mixed
     */
    public function jscode2session($code)
    {
        load_helper('Network');

        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $this->appid . '&secret=' . $this->appsecret . '&js_code=' . $code . '&grant_type=authorization_code';
        $result = http_get($url);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     * @param $encryptedData string 加密的用户数据
     * @param $iv string 与用户数据一同返回的初始向量
     * @param $data string 解密后的原文
     *
     * @return int 成功0，失败返回对应的错误码
     */
    public function decryptData($encryptedData, $iv, $sessionKey)
    {
        if (strlen($sessionKey) != 24) {
            $this->errCode = -41001;
            $this->errMsg = 'sessionKey错误';
            return false;
        }
        $aesKey = base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            $this->errCode = -41002;
            $this->errMsg = 'iv参数错误';
            return false;
        }
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);
        $result = openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $dataObj = json_decode($result);
        if ($dataObj == NULL) {
            $this->errCode = -41003;
            $this->errMsg = '解密失败!';
            return false;
        }
        if ($dataObj->watermark->appid != $this->appid) {
            $this->errCode = -41004;
            $this->errMsg = '解密失败!';
            return false;
        }

        return $result;
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
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errMsg;
    }

    /**
     * 验证权限
     * @param string $appid
     * @param string $appSecret
     * @param string $token
     * @return bool|mixed|string
     */
    public function checkAuth($appid = '', $appSecret = '', $token = '')
    {
        if (!$appid || !$appSecret) {
            $appid = $this->appid;
            $appSecret = $this->appsecret;
        }
        if ($token) { //手动指定token，优先使用
            $this->access_token = $token;
            return $this->access_token;
        }

        $authname = 'wechat_miniapp:access_token:' . $appid;
        if ($rs = $this->getCache($authname)) {
            $this->access_token = $rs;
            return $rs;
        }
        load_helper('Network');
        $result = $this->http_get(self::API_URL_PREFIX . self::AUTH_URL . 'appid=' . $appid . '&secret=' . $appSecret);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            $this->access_token = $json['access_token'];
            $expire = $json['expires_in'] ? intval($json['expires_in']) - 100 : 3600;
            $this->setCache($authname, $this->access_token, $expire);
            return $this->access_token;
        }
        return false;
    }

    /**
     * 设置缓存，按需重载
     * @param string $cachename
     * @param mixed $value
     * @param int $expired
     * @return boolean
     */
    protected function setCache($cachename, $value, $expired)
    {
        \Cache::put($cachename, $value, $expired / 60);
        return true;
    }

    /**
     * 获取缓存，按需重载
     * @param string $cachename
     * @return mixed
     */
    protected function getCache($cachename)
    {
        return \Cache::get($cachename);
    }

    /**
     * GET 请求
     * @param string $url
     */
    public function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
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
     * POST 请求
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
        curl_setopt($oCurl, CURLOPT_POSTFIELDS, $strPOST);
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
     * 发送消息
     */
    public function sendTemplateMessage($data)
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        $result = $this->http_post(self::API_URL_PREFIX . self::TEMPLATE_SEND_URL . 'access_token=' . $this->access_token, self::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }
            return $json;
        }
        return false;
    }

    /**
     * 清除access_token
     * @return string
     */
    public function clearAccessToken()
    {
        return $this->access_token = '';
    }

    /**
     * 微信api不支持中文转义的json结构
     * @param array $arr
     */
    static function json_encode($arr)
    {
        //php5.4 json_encode才支持第二个参数：JSON_UNESCAPED_UNICODE ,中文不会被默认转成unicode
        //官方已修复
        return json_encode($arr, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取小程序码，
     * 适用于需要的码数量极多的业务场景。通过该接口生成的小程序码，永久有效，数量暂无限制。
     * 可传scene场景值
     * 接口只能生成已发布的小程序的二维码
     * @param $save_path string 保存路径
     * @param $params array
     * @return bool|string
     */
    public function getUnlimitWxacode($save_path, $params = array())
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        if (empty($params)) {
            $params = array(
                'scene' => 'defalut'
            );
        }

        $result = $this->http_post(self::WXCODEUNLIMIT_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if (!$result || json_decode($result, true)) {
            return false;
        }

        return file_put_contents($save_path, $result);
    }

    /**
     * 获取小程序码
     * 适用于需要的码数量较少的业务场景。通过该接口生成的小程序码，永久有效，有数量限制
     * 接口只能生成已发布的小程序的二维码
     * @param $save_path string 保存路径
     * @param $params array
     * @return bool|string
     */
    public function getWxaCode($save_path, $params = array())
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        if (empty($params)) {
            $params = array(
                'path' => 'pages/index/index',
            );
        }

        $result = $this->http_post(self::WXACODE_URL . 'access_token=' . $this->access_token, self::json_encode($params));

        if (!$result || json_decode($result, true)) {
            return false;
        }

        return file_put_contents($save_path, $result);
    }

    /**
     * 获取小程序二维码
     * 适用于需要的码数量较少的业务场景,通过该接口生成的小程序码，永久有效，有数量限制
     * 接口只能生成已发布的小程序的二维码
     * @param $save_path string 保存路径
     * @param $params array
     * @return bool|string
     */
    public function createWXAQRCode($save_path, $params = array())
    {
        if (!$this->access_token && !$this->checkAuth()) return false;
        if (empty($params)) {
            $params = array(
                'path' => 'pages/index/index',
            );
        }

        $result = $this->http_post(self::WXAQRCODE_URL . 'access_token=' . $this->access_token, self::json_encode($params));

        if (!$result || json_decode($result, true)) {
            return false;
        }

        return file_put_contents($save_path, $result);
    }


    /*=======  第三方平台 =======*/
    /*  以下是第三方平台接口 */

    /**
     * 设置小程序服务器域名
     * @param
     * @return bool|string
     */
    public function setDomain($action = 'set', $data)
    {

        $params = array(
            'action' => $action,
            'requestdomain' => $data['requestdomain'],
            'wsrequestdomain' => $data['wsrequestdomain'],
            'uploaddomain' => $data['uploaddomain'],
            'downloaddomain' => $data['downloaddomain'],
        );
        $result = $this->http_post(self::SET_DNS_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                switch ($this->errCode) {
                    case 85015:
                        throw new ApiException('该账号不是小程序账号!');
                        break;
                    case 85016:
                        throw new ApiException('域名数量超过限制!');
                        break;
                    case 85017:
                        throw new ApiException('没有新增域名，请确认小程序已经添加了域名或该域名是否没有在第三方平台添加!');
                        break;
                    case 85018:
                        throw new ApiException('域名没有在第三方平台设置!');
                        break;
                    default:
                        throw new ApiException('小程序服务器域名设置出错!');
                        break;
                }
            }

            return true;
        }
        return false;
    }

    /**
     * 设置小程序业务域名
     * @param
     * @return bool|string
     */
    public function setWebViewDomain($action = 'set', $domain)
    {

        $params = array(
            'action' => $action,
            'webviewdomain' => $domain,
        );
        $result = $this->http_post(self::SET_WEB_VIEM_DOMAIN_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                switch ($this->errCode) {
                    case 89019:
                        throw new ApiException('业务域名无更改，无需重复设置!');
                        break;
                    case 89020:
                        throw new ApiException('尚未设置小程序业务域名，请先在第三方平台中设置小程序业务域名后在调用本接口!');
                        break;
                    case 89021:
                        throw new ApiException('请求保存的域名不是第三方平台中已设置的小程序业务域名或子域名!');
                        break;
                    case 89029:
                        throw new ApiException('业务域名数量超过限制!');
                        break;
                    case 89231:
                        throw new ApiException('个人小程序不支持调用setwebviewdomain 接口!');
                        break;
                    default:
                        throw new ApiException('小程序业务域名设置出错!');
                        break;
                }
            }

            return $result;
        }
        return false;
    }

    /**
     * 为授权的小程序帐号上传小程序代码
     * @param
     * @return bool|string
     */
    public function upload($params)
    {
        $result = $this->http_post(self::UPLOAD_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                switch ($this->errCode) {
                    case -1:
                        throw new ApiException('系统繁忙!');
                        break;
                    case 85013:
                        throw new ApiException('无效的自定义配置!');
                        break;
                    case 85014:
                        throw new ApiException('无效的模版编号!');
                        break;
                    case 85043:
                        throw new ApiException('模版错误!');
                        break;
                    case 85045:
                        throw new ApiException('ext_json有不存在的路径!');
                        break;
                    case 85046:
                        throw new ApiException('tabBar中缺少path!');
                        break;
                    case 85047:
                        throw new ApiException('pages字段为空!');
                        break;
                    case 85048:
                        throw new ApiException('ext_json解析失败!');
                        break;
                    default:
                        throw new ApiException('上传出错!');
                        break;
                }
            }

            return true;
        }
        return false;
    }

    /**
     * 获取小程序体验二维码
     * @param
     * @return bool|string
     */
    public function getExperienceQrcode($save_path)
    {
        $result = $this->http_get(self::QRCODE_URL . 'access_token=' . $this->access_token);

        if (!$result || json_decode($result, true)) {
            return false;
        }

        return file_put_contents($save_path, $result);
    }

    /**
     * 为授权的小程序帐号上传小程序代码
     * @param
     * @return bool|string
     */
    public function getCategory()
    {
        $result = $this->http_get(self::CATEGORY_URL . 'access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * 绑定微信用户为小程序体验者
     * @param
     * @return bool|string
     */
    public function getTesterList()
    {
        $params = array(
            'action' => 'get_experiencer',
        );

        $result = $this->http_post(self::GET_TESTER_LIST_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * 绑定微信用户为小程序体验者
     * @param
     * @return bool|string
     */
    public function bindTester($wechatid)
    {
        $params = array(
            'wechatid' => $wechatid,
        );

        $result = $this->http_post(self::BIND_TESTER_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * 解绑微信用户为小程序体验者
     * @param
     * @return bool|string
     */
    public function unbindTester($wechatid = '', $userstr = '')
    {
        $params = array(
        );

        empty($wechatid) ? $params['userstr'] = $userstr : $params['wechatid'] = $wechatid;

        $result = $this->http_post(self::UNBIND_TESTER_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * 小程序的页面配置
     * @param
     * @return bool|string
     */
    public function getPage()
    {
        $result = $this->http_get(self::GET_PAGE_URL . 'access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * 小程序提交审核
     * @param
     * @return bool|string
     */
    public function wechatminiCheck($params)
    {
        $result = $this->http_post(self::CHECK_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                switch ($this->errCode) {
                    case -1:
                        throw new ApiException('系统繁忙!');
                        break;
                    case 86000:
                        throw new ApiException('不是由第三方代小程序进行调用!');
                        break;
                    case 86001:
                        throw new ApiException('不存在第三方的已经提交的代码!');
                        break;
                    case 85006:
                        throw new ApiException('标签格式错误!');
                        break;
                    case 85007:
                        throw new ApiException('页面路径错误!');
                        break;
                    case 85008:
                        throw new ApiException('类目填写错误!');
                        break;
                    case 85009:
                        throw new ApiException('已经有正在审核的版本!');
                        break;
                    case 85010:
                        throw new ApiException('item_list有项目为空!');
                        break;
                    case 85011:
                        throw new ApiException('标题填写错误!');
                        break;
                    case 85023:
                        throw new ApiException('审核列表填写的项目数不在1-5以内!');
                        break;
                    case 85077:
                        throw new ApiException('小程序类目信息失效（类目中含有官方下架的类目，请重新选择类目）!');
                        break;
                    case 86002:
                        throw new ApiException('小程序还未设置昵称、头像、简介。请先设置完后再重新提交。');
                        break;
                    case 85085:
                        throw new ApiException('近7天提交审核的小程序数量过多，请耐心等待审核完毕后再次提交!');
                        break;
                    case 85086:
                        throw new ApiException('提交代码审核之前需提前上传代码');
                        break;
                    default:
                        throw new ApiException('审核出错!');
                        break;
                }
            }

            return $json;
        }
        return false;
    }

    /**
     * 查询某个指定版本的审核状态
     * @param
     * @return bool|string
     */
    public function getAuditstatus($auditid)
    {
        $params = array(
            'auditid' => $auditid,
        );

        $result = $this->http_post(self::AUDITSTATUS_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * 查询某个指定版本的审核状态
     * @param
     * @return bool|string
     */
    public function undoCodeAudit()
    {
        $result = $this->http_get(self::UNDOCODEAUDIT_URL . 'access_token=' . $this->access_token);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return false;
            }

            return $json;
        }
        return false;
    }

    /**
     * 发布已通过审核的小程序
     * @param
     * @return bool|string
     */
    public function release()
    {
        $params = array();
        $result = $this->http_post(self::RELEASE_URL . 'access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                switch ($this->errCode) {
                    case -1:
                        throw new ApiException('系统繁忙!');
                        break;
                    case 85019:
                        throw new ApiException('没有审核版本!');
                        break;
                    case 85020:
                        throw new ApiException('审核状态未满足发布!');
                        break;
                    case 85052:
                        throw new ApiException('小程序已发布!');
                        break;
                    default:
                        throw new ApiException('发布出错!');
                        break;
                }
            }

            return $json;
        }
        return false;
    }

    /**
     * 解绑小程序
     * @param
     * @return bool|string
     */
    public function unbind($appid)
    {
        $params = array(
            'appid' => $appid,
            'open_appid' => $this->appid,
        );

        $result = $this->http_post('https://api.weixin.qq.com/cgi-bin/open/unbind?access_token=' . $this->access_token, self::json_encode($params));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['errcode'] != 0) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                switch ($this->errCode) {
                    case -1:
                        throw new ApiException('系统繁忙!');
                        break;
                    case 40013:
                        throw new ApiException('appid无效!');
                        break;
                    case 89002:
                        throw new ApiException('该公众号/小程序未绑定微信开放平台帐号!');
                        break;
                    default:
                        throw new ApiException('解绑出错!');
                        break;
                }
            }

            return $json;
        }
        return false;
    }

}