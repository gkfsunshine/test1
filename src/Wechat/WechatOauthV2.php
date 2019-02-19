<?php

namespace JiaLeo\Laravel\Wechat;

use App\Exceptions\ApiException;

/**
 * 微信授权
 */
class WechatOauthV2
{

    public $weObj;  //微信实例
    public $isComponent = false;  //是否为第三方平台
    public $authorizerAppid;       //授权者appid
    public $authOauthModel = '\App\Model\UserAuthOauthModel';
    public $authOauthUserField = 'user_id';

    private $accessToken;  //code获取的access_token

    /**
     * 注释说明
     * @param $params
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function __construct($params)
    {
        //实例化微信类
        $weObj = new Wechat($params['type']);

        $this->weObj = $weObj;
        $this->params = $params;

        $query['type'] = $params['type'];

        //如果携带了callback
        if (isset($_GET['callback'])) {
            $query['callback'] = $_GET['callback'];
        }

        //如果携带了token
        if (isset($params['token'])) {
            $query['token'] = $params['token'];
        }

        if (!empty($params['extra_params'])) {
            foreach ($params['extra_params'] as $v) {
                if (empty($_GET[$v])) {
                    throw new ApiException('参数错误!');
                }
                $query[$v] = $_GET[$v];
            }
        }

        $this->callback = url()->current() . '?' . http_build_query($query);
    }

    /**
     * 运行
     * @return array|bool|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws ApiException
     */
    public function run()
    {
        if (empty($_GET['code']) && empty($_GET['state'])) {    //第一步
            return $this->firstStep();
        } elseif (!empty($_GET['code']) && $_GET['state'] == 'snsapi_base') { //静默请求获得openid
            return $this->afterSilentOauth();
        } elseif (!empty($_GET['code']) && $_GET['state'] == 'snsapi_userinfo') {   //弹出授权获取用户消息
            return $this->afterClickOauth();
        } else {
            return false;
        }
    }

    /**
     * 授权登录第一步
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function firstStep()
    {
        if ($this->isComponent) {
            $reurl = $this->weObj->getComponentOauthRedirect($this->callback, "snsapi_base", "snsapi_base", $this->authorizerAppid);
        } else {
            $reurl = $this->weObj->getOauthRedirect($this->callback, "snsapi_base", "snsapi_base");
        }
        return redirect($reurl);
    }

    /**
     * 静默获取授权后逻辑
     * @author: 亮 <chenjialiang@han-zi.cn>
     * @return mixed 用户给的回调函数返回true,则返回该用户openid,反之则跳转用户点击授权
     * @throws ApiException
     */
    public function afterSilentOauth()
    {
        if (empty($_GET['code'])) {
            //TODO  跳转取消授权页
            throw new ApiException('用户取消授权!');
        }

        $accessToken = $this->isComponent ? $this->weObj->getComponentOauthAccessToken($this->authorizerAppid) : $this->weObj->getOauthAccessToken();
        if (!$accessToken || empty($accessToken['openid'])) {
            throw new ApiException('code错误', 'CODE_ERROR');
        }

        $this->accessToken = $accessToken;

        //使用unionid作为用户标识
        if ($this->params['check_for'] === 'unionid') {
            $get_unionid = $this->weObj->getUserInfo($this->accessToken['openid']);
            if (!isset($get_unionid['unionid'])) {
                throw new ApiException('获取unionid失败!错误码:' . $this->weObj->errCode . ' 错误信息:' . $this->weObj->errMsg, 'UNIONID_ERROR');
            }

            $this->accessToken['unionid'] = $get_unionid['unionid'];
        }

        //是否存在用户
        $user_info = $this->checkUser();
        if (!$user_info && $this->params['is_oauth_user_info'] === true) {        //不存在用户 且 设置为通过显性授权获取用户信息
            $reurl = $this->isComponent ? $this->weObj->getComponentOauthRedirect($this->callback, "snsapi_userinfo", "snsapi_userinfo", $this->authorizerAppid) : $this->weObj->getOauthRedirect($this->callback, "snsapi_userinfo", "snsapi_userinfo");
            return redirect($reurl);
        } elseif (!$user_info && $this->params['is_oauth_user_info'] === false) {
            //创健新用户
            $add_user = call_user_func_array($this->params['create_user_function'], array($this->accessToken));

            if ($this->params['type'] == 'app') {
                return $add_user;
            } else {
                return redirect(urldecode($_GET['callback']));
            }

        } elseif ($user_info) {       //存在用户
            $result = call_user_func_array($this->params['oauth_get_user_silent_function'], array($user_info));

            if (!$result) {
                throw new ApiException('操作失败,请联系管理员!');
            }

            if ($this->params['type'] == 'app') {
                return $result;
            } else {
                return redirect(urldecode($_GET['callback']));
            }

        } else {
            throw new ApiException('授权失败', 'AUTH_ERROR');
        }

    }

    /**
     * 用户点击授权后逻辑
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function afterClickOauth()
    {
        if (empty($_GET['code'])) {
            //TODO  跳转取消授权页
            throw new ApiException('用户取消授权!');
        }

        $accessToken = $this->isComponent ? $this->weObj->getComponentOauthAccessToken($this->authorizerAppid) : $this->weObj->getOauthAccessToken();
        if (!$accessToken || empty($accessToken['openid'])) {
            throw new ApiException('code错误', 'CODE_ERROR');
        }

        $this->accessToken = $accessToken;
        //拉取用户信息
        $user_info = $this->weObj->getOauthUserinfo($accessToken['access_token'], $accessToken['openid']);
        if (!$user_info) {
            throw new ApiException('获取用户信息失败!', 'GET_USERINFO_ERROR');
        }

        if (isset($user_info['unionid'])) {
            $this->accessToken['unionid'] = $user_info['unionid'];
        }

        //记录登录方式检验方式
        $user_info['type'] = $this->params['type'];
        $user_info['check_for'] = $this->params['check_for'];

        $user_info = array_merge($user_info, $this->accessToken);

        //检查是否存在用户
        $is_user = $this->checkUser();

        if (!$is_user) {
            //创健新用户
            $add_user = call_user_func_array($this->params['create_user_function'], array($user_info));
            $user_id = $add_user;
        } else {
            $user_id = $is_user['user_id'];
        }

        $result = call_user_func_array($this->params['oauth_get_user_info_function'], array($user_id, $user_info));

        if (!$result) {
            throw new ApiException('授权失败', 'AUTH_ERROR');
        }

        if ($this->params['type'] == 'app') {
            return $result;
        } else {
            return redirect(urldecode($_GET['callback']));
        }
    }

    /**
     * 检查是否存在用户
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function checkUser()
    {
        if (empty($this->accessToken) || empty($this->accessToken['openid'])) {
            throw new ApiException('获取openid错误!', 'ACCESS_TOKEN_ERROR');
        }

        $openid = $this->accessToken['openid'];
        $unionid = '';

        //使用unionid作为用户标识
        if ($this->params['check_for'] === 'unionid') {
            if (empty($this->accessToken['unionid'])) {
                throw new ApiException('获取unionid错误!', 'UNIONID_ERROR');
            }

            $unionid = $this->accessToken['unionid'];

            $where = array(
                'id2' => $unionid,
                'oauth_type' => 1
            );
        } else {

            $where = array(
                'id1' => $openid,
                'oauth_type' => 1
            );
        }

        $model = $this->authOauthModel;
        $user_field = $this->authOauthUserField;

        $is_user = $model::where($where)->first(['id', $user_field, 'id1']);
        if (!$is_user) {
            return false;
        }

        //返回的用户信息
        $user_info = array(
            'openid' => $openid,
            'unionid' => $unionid,
            'user_id' => $is_user[$user_field],
            'type' => $this->params['type'],
            'check_for' => $this->params['check_for']
        );

        //保存更新信息
        $up = [
            'access_token' => $this->accessToken['access_token'],
            'expires_time' => time() + $this->accessToken['expires_in'],
            'refresh_token' => $this->accessToken['refresh_token'],
            'info' => json_encode($this->accessToken)
        ];

        //如果是公众号,则判断有没有记录openid,没有则更新openid
        if ($this->params['type'] == 'mp' && empty($is_user['id1'])) {
            $up['id1'] = $this->accessToken['openid'];
        }

        $user_auth_oauth = $model::where('id', $is_user['id'])
            ->update($up);
        if (!$user_auth_oauth) {
            throw new ApiException('更新授权信息失败!');
        }

        return $user_info;
    }
}
