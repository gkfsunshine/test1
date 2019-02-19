<?php

namespace JiaLeo\Laravel\Wechat;

use App\Exceptions\ApiException;
use Psy\Command\DumpCommand;

/**
 * 微信授权
 */
class WechatOauth
{

    public $weObj;  //微信实例
    private $access_token;  //code获取的access_token

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

        if (isset($_GET['callback'])) {
            $query['callback'] = $_GET['callback'];
        }

        if (isset($params['token'])) {
            $query['token'] = $params['token'];
        }

        $query['type'] = $params['type'];
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
        } elseif (!empty($_GET['code']) && ($_GET['state'] == 'snsapi_userinfo' || $_GET['state'] == 'snsapi_privateinfo')) {   //弹出授权获取用户消息
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
        //2017年12月13日微信授权调整用户unionID获取策略调整
        if ($this->params['check_for'] === 'unionid') {
            $reurl = $this->weObj->getOauthRedirect($this->callback, "snsapi_userinfo", "snsapi_userinfo");
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

        if ($this->params['type'] == 'qiyemp') {        //企业号授权
            $accessToken = $this->weObj->checkQiyeAuth();
            if (!$accessToken) {
                throw new ApiException('code错误', 'CODE_ERROR');
            }
            //获取UserId,企业微信唯一标示是UserId
            $ticket_info = $this->weObj->getQiYeUserInfo();

            if (!$ticket_info) {
                throw new ApiException('授权失败', 'OAUTH_ERROR');
            }

            if (!isset($ticket_info['UserId'])) {
                throw new ApiException('非企业员工', 'QIYE_USER_ERROR');
            }

            //类似公众号将唯一标示拼进accessToken数组中去
            $accessToken['UserId'] = $ticket_info['UserId'];

        } else {          //公众号授权
            $accessToken = $this->weObj->getOauthAccessToken();
            if (!$accessToken || empty($accessToken['openid'])) {
                throw new ApiException('code错误', 'CODE_ERROR');
            }
        }

        $this->access_token = $accessToken;

        //使用unionid作为用户标识
        if ($this->params['check_for'] === 'unionid') {
            $get_unionid = $this->weObj->getUserInfo($this->access_token['openid']);
            if (!isset($get_unionid['unionid'])) {
                throw new ApiException('获取unionid失败!错误码:' . $this->weObj->errCode . ' 错误信息:' . $this->weObj->errMsg, 'UNIONID_ERROR');
            }

            $this->access_token['unionid'] = $get_unionid['unionid'];
        }

        //是否存在用户
        $user_info = $this->checkUser();
        if (!$user_info && $this->params['is_oauth_user_info'] === true) {        //不存在用户 且 设置为通过显性授权获取用户信息
            if ($this->params['type'] == 'qiyemp') {
                $scope = "snsapi_privateinfo";
            } else {
                $scope = "snsapi_userinfo";
            }
            $reurl = $this->weObj->getOauthRedirect($this->callback, $scope, $scope);
            return redirect($reurl);
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

        } elseif (!$user_info && $this->params['is_oauth_user_info'] === false) {       //隐性授权

            $user_info['openid'] = $this->access_token['openid'];
            if (isset($this->access_token['unionid'])) {
                $user_info['unionid'];
            }
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
        if ($this->params['type'] == 'qiyemp') {
            $accessToken = $this->weObj->checkQiyeAuth();
            if (!$accessToken) {
                throw new ApiException('code错误', 'CODE_ERROR');
            }
            //获取UserId,企业微信唯一标示是UserId
            $ticket_info = $this->weObj->getQiYeUserInfo();
            if (!$ticket_info) {
                throw new ApiException('授权失败', 'OAUTH_ERROR');
            }
            if (!isset($ticket_info['UserId']) || !isset($ticket_info['user_ticket'])) {
                throw new ApiException('非企业员工', 'QIYE_USER_ERROR');
            }
        } else {
            $accessToken = $this->weObj->getOauthAccessToken();
            if (!$accessToken || empty($accessToken['openid'])) {
                throw new ApiException('code错误', 'CODE_ERROR');
            }
        }

        $this->access_token = $accessToken;

        //
        if ($this->params['type'] == 'qiyemp') {
            $user_info = $this->weObj->getQiYeUserDetail($ticket_info['user_ticket']);
            if (!$user_info) {
                throw new ApiException('获取用户信息失败!', 'GET_USERINFO_ERROR');
            }
            $this->access_token['UserId'] = $ticket_info['UserId'];
        } else {
            //拉取用户信息
            $user_info = $this->weObj->getOauthUserinfo($accessToken['access_token'], $accessToken['openid']);
            if (!$user_info) {
                throw new ApiException('获取用户信息失败!', 'GET_USERINFO_ERROR');
            }

            if (isset($user_info['unionid'])) {
                $this->access_token['unionid'] = $user_info['unionid'];
            }
        }

        //记录登录方式检验方式
        $user_info['type'] = $this->params['type'];
        $user_info['check_for'] = $this->params['check_for'];

        //检查是否存在用户
        if ($this->params['oauth_get_user_info_function'])
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

        if ($this->params['type'] == 'qiyemp') {
            if (empty($this->access_token) || empty($this->access_token['UserId'])) {
                throw new ApiException('获取UserId错误!', 'USERID_ERROR');
            }
            $openid = $this->access_token['UserId'];
        } else {
            if (empty($this->access_token) || empty($this->access_token['openid'])) {
                throw new ApiException('获取openid错误!', 'ACCESS_TOKEN_ERROR');
            }
            $openid = $this->access_token['openid'];
        }

        $unionid = '';

        //使用unionid作为用户标识
        if ($this->params['check_for'] === 'unionid') {
            if (empty($this->access_token['unionid'])) {
                throw new ApiException('获取unionid错误!', 'UNIONID_ERROR');
            }

            $unionid = $this->access_token['unionid'];

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

        if ($this->params['type'] == 'qiyemp') {
            $is_user = \App\Model\QiyeAuthOauthModel::where($where)->first(['id', 'qiye_user_id', 'id1']);
        } else {
            $is_user = \App\Model\UserAuthOauthModel::where($where)->first(['id', 'user_id', 'id1']);
        }
        if (!$is_user) {
            return false;
        }

        //返回的用户信息
        $user_info = array(
            'openid' => $openid,
            'unionid' => $unionid,
            'type' => $this->params['type'],
            'check_for' => $this->params['check_for']
        );

        if ($this->params['type'] == 'qiyemp') {
            $user_info['qiye_user_id'] = $is_user->qiye_user_id;
        } else {
            $user_info['user_id'] = $is_user->user_id;
        }

        //保存更新信息
        $up = [
            'access_token' => $this->access_token['access_token'],
            'expires_time' => time() + $this->access_token['expires_in'],
            'info' => json_encode($this->access_token)
        ];

        //如果是公众号,则判断有没有记录openid,没有则更新openid
        if ($this->params['type'] == 'mp' && empty($is_user['id1'])) {
            $up['id1'] = $this->access_token['openid'];
        }

        if ($this->params['type'] == 'qiyemp') {
            $user_auth_oauth = \App\Model\QiyeAuthOauthModel::where('id', $is_user['id'])
                ->update($up);
        } else {
            $user_auth_oauth = \App\Model\UserAuthOauthModel::where('id', $is_user['id'])
                ->update($up);
        }

        if (!$user_auth_oauth) {
            throw new ApiException('更新授权信息失败!');
        }

        return $user_info;
    }
}