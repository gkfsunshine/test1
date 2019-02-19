<?php


namespace JiaLeo\Laravel\Oauth;


use App\Exceptions\ApiException;
use App\Model\UserAuthOauthModel;
use App\Model\UserModel;

class Oauth
{

    public $appid;
    public $appkey;
    public $redirect_uri;
    public $token;
    public $device;
    public $check_for;
    public $get_user_function;
    public $create_user_function;
    public $callback_url;

    public $oauth_type = 'QQ';   //QQ,Weibo

    private $oauthObj;

    public function __construct($params = array())
    {

        $this->oauth_type = isset($params['oauth_type']) ? $params['oauth_type'] : 'QQ';
        $this->device = isset($params['device']) ? $params['device'] : 'web';
        $this->check_for = isset($params['check_for']) ? $params['check_for'] : 'openid';
        $this->redirect_uri = isset($params['redirect_uri']) ? $params['redirect_uri'] : 'http://127.0.0.1';
        $this->get_user_function = isset($params['get_user_function']) ? $params['get_user_function'] : array();
        $this->create_user_function = isset($params['create_user_function']) ? $params['create_user_function'] : array();
        $this->token = isset($params['token']) ? $params['token'] : '';
        $this->callback_url = isset($params['callback_url']) ? $params['callback_url'] : '';

        //读取配置文件
        $key = strtolower($this->oauth_type);
        $config = config('oauth.' . $key . '.' . $this->device);
        $this->appid = $config['appid'];
        $this->appkey = $config['appkey'];

        //实例化驱动
        $store_class = 'JiaLeo\Laravel\Oauth\\' . ucfirst($this->oauth_type) . 'Driver';

        if (!class_exists($store_class)) {
            throw new ApiException('Oauth Driver [' . $this->oauth_type . '] is not supported.');
        }

        $this->oauthObj = new $store_class();

        $this->oauthObj->appid = $this->appid;
        $this->oauthObj->appkey = $this->appkey;
        $this->oauthObj->redirect_uri = $this->redirect_uri;
    }

    /**
     * 运行
     * @return bool|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * @throws ApiException
     */
    public function run()
    {

//        if ($this->device == 'app' && !empty(request()->get('openid')) && !empty(request()->get('access_token'))) { //QQ app授权
//            return $this->doForOpenidAndAccesstoken(request()->get('openid'), request()->get('access_token'));
//        } elseif (empty(request()->get('code')) && empty(request()->get('state'))) {
//            //跳转以获取Authorization Code
//            return $this->redirectToGetAuthorizationCode();
//        } elseif (!empty(request()->get('code') && !empty(request()->get('state')))) {
//            //授权获取code和state后
//            return $this->doForCodeAndState(request()->get('code'), request()->get('state'));
//        }
        if ($this->device == 'app'){  //App
            if ($this->oauth_type == 'QQ' &&!empty(request()->get('openid'))&&!empty(request()->get('access_token'))){  //QQ
                return $this->doForOpenidAndAccesstoken(request()->get('openid'), request()->get('access_token'));
            }elseif($this->oauth_type == 'Weibo' &&!empty(request()->get('uid'))&&!empty(request()->get('access_token'))){  //微博
                return $this->doForUidAndAccesstoken(request()->get('uid'),request()->get('access_token'));
            }
            return false;
        }else{  //网页
            if (empty(request()->get('code')) && empty(request()->get('state'))) {
                //跳转以获取Authorization Code
                return $this->redirectToGetAuthorizationCode();
            } elseif (!empty(request()->get('code') && !empty(request()->get('state')))) {
                //授权获取code和state后
                return $this->doForCodeAndState(request()->get('code'), request()->get('state'));
            }
        }
    }

    /**
     * 重定向获取code
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirectToGetAuthorizationCode()
    {
        $state = str_random(13);

        $redirect_uri = $this->redirect_uri . '?token=' . $this->token . '&callback_url=' . urlencode($this->callback_url) . '&oauth_type=' . $this->oauth_type . '&device=' . $this->device;
        \Jwt::set('oauth.' . strtolower($this->oauth_type) . 'state', $state);

        return redirect($this->oauthObj->getAuthUrl($redirect_uri, $state));
    }


    /**
     * 获取code和state后操作
     * @param $code
     * @param $state
     * @return bool
     * @throws ApiException
     */
    public function doForCodeAndState($code, $state)
    {
        //验证state
        $save_state = \Jwt::get('oauth.' . strtolower($this->oauth_type) . 'state');

        if ($save_state != $state) {
            throw new ApiException('验证失败,请重试授权!');
        }

        //获取openid
        $user_openid = $this->oauthObj->getOpenidByCode($this->redirect_uri, $code);
        /*
         * 返回数据
         * array(
         *    'openid',
         *    'access_token',
         *    'expires_in'
         * )
         */


        if ($this->check_for == 'union_id'&&$this->oauth_type == 2){
            $user_unionid = $this->oauthObj->getUnionidByAccessToken($user_openid['access_token']);
            $is_user = $this->checkUserIsExist($user_unionid);
        }else{
            $is_user = $this->checkUserIsExist($user_openid);
        }

        /*
         * 返回数据
         * $user_info = array(
         *      'oauth_id' ,
         *      'openid' ,
         *      'user_id' ,
         *      'oauth_type'
         *  );
         *
         * */

        if (!$is_user) {
            //获取用户信息
            $user_info = $this->oauthObj->getUserInfo($user_openid['openid'], $user_openid['access_token']);
            if (!$user_info) {
                throw new ApiException('获取用户信息失败!');
            }

            /*
             * 返回数据
             * return array(
             *      'nickname' ,
             *      'headimg',
             *      'openid' ,
             *      'province' ,
             *      'city',
             *      'sex'
             *  );
             */

            $all_info = array_merge($user_info, $user_openid);
            $all_info = isset($user_unionid)?array_merge($all_info, $user_unionid):$all_info;
            \Log::info($all_info);
            //创健新授权用户
            $oauth_id = $this->addOauth($all_info);
            $all_info = array_merge($all_info, ['oauth_id' => $oauth_id]);
            if (empty($oauth_id)) {
                throw new ApiException('添加用户失败');
            }
            $all_info['oauth_type'] = strtolower($this->oauth_type);
            //调用自定义添加用户方法
            call_user_func_array($this->create_user_function, array($all_info));

        } else {
            //已有用户
            $is_user['oauth_type'] = strtolower($this->oauth_type);
            call_user_func_array($this->get_user_function, array($is_user));

        }

        return redirect(urldecode($this->callback_url));
    }

    public function doForOpenidAndAccesstoken($openid, $access_token)
    {
        $user_unionid = $this->oauthObj->getUnionidByAccessToken($access_token);
        $is_user = $this->checkUserIsExist($user_unionid);
        if (!$is_user) {
            //获取用户信息
            $user_info = $this->oauthObj->getUserInfo($openid, $access_token);
            if (!$user_info) {
                throw new ApiException('获取用户信息失败!');
            }

            $all_info = $user_info;
            $all_info = array_merge($all_info, $user_unionid);

            //创健新授权用户
            $oauth_id = $this->addOauth($all_info);
            if (empty($oauth_id)) {
                throw new ApiException('添加用户失败');
            }
            $all_info['oauth_type'] = strtolower($this->oauth_type);
            $all_info = array_merge($all_info, ['oauth_id' => $oauth_id]);
            //调用自定义添加用户方法
            call_user_func_array($this->create_user_function, array($all_info));
        } else {
            //已有用户
            $is_user['oauth_type'] = strtolower($this->oauth_type);
            call_user_func_array($this->get_user_function, array($is_user));

        }
        return true;
    }


    public function doForUidAndAccesstoken($uid, $access_token)
    {
        $user_unionid = array('unionid'=>$uid,'openid'=>$uid);
        $is_user = $this->checkUserIsExist($user_unionid);
        if (!$is_user) {
            //获取用户信息
            $user_info = $this->oauthObj->getUserInfo($uid, $access_token);
            if (!$user_info) {
                throw new ApiException('获取用户信息失败!');
            }
            \Log::info($user_info);
            $all_info = $user_info;
            $all_info = array_merge($all_info, $user_unionid);

            //创健新授权用户
            $oauth_id = $this->addOauth($all_info);
            if (empty($oauth_id)) {
                throw new ApiException('添加用户失败');
            }
            $all_info['oauth_type'] = strtolower($this->oauth_type);
            $all_info = array_merge($all_info, ['oauth_id' => $oauth_id]);
            //调用自定义添加用户方法
            call_user_func_array($this->create_user_function, array($all_info));
        } else {
            //已有用户
            $is_user['oauth_type'] = strtolower($this->oauth_type);
            call_user_func_array($this->get_user_function, array($is_user));

        }
        return true;
    }

    /**
     * 新增授权用户记录
     * @param $user_info
     * @return int
     * @throws ApiException
     */
    public function addOauth($user_info)
    {
        switch ($this->oauth_type) {
            case 'QQ':
                $oauth_type = 2;
                break;
            case 'Weibo':
                $oauth_type = 3;
                break;
            default:
                throw new ApiException('错误的授权类型!');
        }

        $data = [
            'nickname' => $user_info['nickname'],
            'headimg' => $user_info['headimg'],
            'oauth_type' => $oauth_type,
            'id1' => $user_info['openid'],
            'id2' => isset($user_info['unionid'])?$user_info['unionid']:'',
//            'access_token' => $user_info['access_token'],
//            'expires_time' => $user_info['expires_in'],
//            'refresh_token' => $user_info['refresh_token'],
            'info' => json_encode($user_info)
        ];
        $oauth_model = new \App\Model\UserAuthOauthModel();
        set_save_data($oauth_model, $data);
        $res = $oauth_model->save();
        if (!$res) {
            throw new ApiException('数据库错误!');
        }

        return $oauth_model->id;
    }

    /**
     * 检查是否存在用户
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function checkUser($access_info)
    {

        $openid = $access_info['openid'];

        switch ($this->oauth_type) {
            case 'QQ':
                $oauth_type = 2;
                break;
            case 'Weibo':
                $oauth_type = 3;
                break;
            default:
                throw new ApiException('错误的授权类型!');
        }


        $where = array(
            'id1' => $openid,
            'oauth_type' => $oauth_type
        );

        $is_user = \App\Model\UserAuthOauthModel::where($where)->first(['id', 'user_id', 'id1']);
        if (!$is_user) {
            return false;
        }

        //返回的用户信息
        $user_info = array(
            'oauth_id' => $is_user->id,
            'openid' => $openid,
            'user_id' => $is_user->user_id,
            'oauth_type' => $this->oauth_type
        );

        //保存更新信息
        $up = [
            'access_token' => $access_info['access_token'],
            'expires_time' => $access_info['expires_in'],
            'info' => json_encode($access_info)
        ];

        $user_auth_oauth = \App\Model\UserAuthOauthModel::where('id', $user_info['oauth_id'])
            ->update($up);
        if (!$user_auth_oauth) {
            throw new ApiException('更新授权信息失败!');
        }

        return $user_info;
    }

    public function checkUserIsExist($user_auth_info)
    {
        \Log::info($user_auth_info);
        switch ($this->oauth_type) {
            case 'QQ':
                $oauth_type = 2;
                break;
            case 'Weibo':
                $oauth_type = 3;
                break;
            default:
                throw new ApiException('错误的授权类型!');
        }
        if ($this->check_for == 'union_id'&&$this->oauth_type == 2){
            $where = array(
                'id2' => $user_auth_info['unionid'],
                'oauth_type' => $oauth_type
            );
        }else{
            $where = array(
                'id1' => $user_auth_info['openid'],
                'oauth_type' => $oauth_type
            );
        }

        $user = UserAuthOauthModel::where($where)->first();
        if (empty($user)) {
            return false;
        }
        $user_info = array(
            'oauth_id' => $user->id,
            'openid' => $user_auth_info['openid'],
            'user_id' => $user->user_id,
            'oauth_type' => $this->oauth_type
        );

        return $user_info;
    }


}
