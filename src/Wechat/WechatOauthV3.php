<?php

namespace JiaLeo\Laravel\Wechat;

use App\Exceptions\ApiException;
use Psy\Command\DumpCommand;

/**
 * 微信授权
 */
class WechatOauthV3
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

        if ($this->params['check_for'] === 'unionid' && $this->params['oauth_type'] == 'base') {
            throw new ApiException('配置错误!');
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
        }

        if (!empty($_GET['code']) && !empty($_GET['state'])) {
            //验证state
            $state_info = \Jwt::get('wechat_oauth');
            if (!$state_info || $state_info['expires_time'] < time()) {
                throw new ApiException('授权错误,请重试尝试~');
            }

            if ($state_info['type'] == 'base') {
                \Jwt::delete('wechat_oauth');
                return $this->afterSilentOauth();
            } else if ($state_info['type'] == 'user_info') {
                \Jwt::delete('wechat_oauth');
                return $this->afterClickOauth();
            } else {
                throw new ApiException('授权错误,请重试尝试~');
            }
        } else {
            throw new ApiException('授权错误,请重试尝试~');
        }
    }

    /**
     * 授权登录第一步
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function firstStep()
    {
        $state = str_random(16);

        \Jwt::set('wechat_oauth', array(
            'state' => $state,
            'type' => $this->params['oauth_type'],
            'expires_time' => time() + 60 * 5   //过期时间5分钟
        ));

        if ($this->params['type'] == 'app') {
            return array(
                'state' => $state
            );
        } else {
            if ($this->params['oauth_type'] === 'user_info') {
                $reurl = $this->weObj->getOauthRedirect($this->callback, $state, "snsapi_userinfo");
            } else if ($this->params['oauth_type'] === 'base') {
                $reurl = $this->weObj->getOauthRedirect($this->callback, $state, "snsapi_base");
            } else {
                throw new ApiException('oauth_type配置错误!');
            }

            return redirect($reurl);
        }
    }

    /**
     * 静默获取授权后逻辑
     * @author: 亮 <chenjialiang@han-zi.cn>
     * @return mixed 用户给的回调函数返回true,则返回该用户openid,反之则跳转用户点击授权
     * @throws ApiException
     */
    public function afterSilentOauth()
    {

        $access_token = $this->weObj->getOauthAccessToken();
        if (!$access_token || empty($access_token['openid'])) {
            throw new ApiException('code错误', 'CODE_ERROR');
        }

        $this->access_token = $access_token;

        //是否存在用户
        $user_info = $this->checkUser();

        if (!$user_info) {        //用户不存在,执行create_user_function
            //创健新用户
            $accessToken['type'] = $this->params['type'];
            $result = call_user_func_array($this->params['create_user_function'], array($access_token));

        } else {                 //存在用户
            $result = call_user_func_array($this->params['oauth_get_user_silent_function'], array($user_info));

            if (!$result) {
                throw new ApiException('操作失败,请联系管理员!');
            }
        }


        if ($this->params['type'] == 'app') {
            return $result;
        } else {
            return redirect(urldecode($_GET['callback']));
        }
    }

    /**
     * 用户点击授权后逻辑
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function afterClickOauth()
    {
        $access_token = $this->weObj->getOauthAccessToken();
        if (!$access_token || empty($access_token['openid'])) {
            throw new ApiException('code错误', 'CODE_ERROR');
        }

        $this->access_token = $access_token;

        //拉取用户信息
        $user_info = $this->weObj->getOauthUserinfo($access_token['access_token'], $access_token['openid']);
        if (!$user_info) {
            throw new ApiException('获取用户信息失败!', 'GET_USERINFO_ERROR');
        }

        if (isset($user_info['unionid'])) {
            $this->access_token['unionid'] = $user_info['unionid'];
        }

        $is_user = $this->checkUser();

        $info = array_merge($access_token, $user_info);
        $info['type'] = $this->params['type'];
        if (!$is_user) {
            //创健新用户
            $result = call_user_func_array($this->params['create_user_function'], array($info));
        } else {
            $user_id = $is_user['user_id'];
            $result = call_user_func_array($this->params['oauth_get_user_info_function'], array($user_id, $user_info));
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

        if (empty($this->access_token) || empty($this->access_token['openid'])) {
            throw new ApiException('获取openid错误!', 'ACCESS_TOKEN_ERROR');
        }
        $openid = $this->access_token['openid'];
        $unionid = $this->access_token['unionid'] ?? '';

        //使用unionid作为用户标识
        if ($this->params['check_for'] === 'unionid') {
            if (empty($unionid)) {
                throw new ApiException('获取unionid错误!', 'UNIONID_ERROR');
            }

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

        $is_user = \App\Model\UserAuthOauthModel::where($where)->first(['id', 'user_id', 'id1']);
        if (!$is_user) {
            return false;
        }

        //保存更新信息
        $update_data = [
            'access_token' => $this->access_token['access_token'],
            'expires_time' => time() + $this->access_token['expires_in'],
            'info' => json_encode($this->access_token)
        ];

        //如果是公众号,则判断有没有记录openid,没有则更新openid
        if ($this->params['type'] == 'mp' && empty($is_user['id1'])) {
            $up['id1'] = $this->access_token['openid'];
        }

        $user_auth_oauth = \App\Model\UserAuthOauthModel::where('id', $is_user['id'])
            ->update($update_data);


        if (!$user_auth_oauth) {
            throw new ApiException('更新授权信息失败!');
        }

        //返回的用户信息
        $user_info = array(
            'openid' => $openid,
            'unionid' => $unionid,
            'type' => $this->params['type'],
            'user_id' => $is_user->user_id
        );

        return $user_info;
    }
}
