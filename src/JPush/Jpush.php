<?php
namespace JiaLeo\Laravel\Jpush;

use JPush\Client;

class Jpush
{

    private static $config; //配置
    private static $app_key;   //app_key
    private static $master_secret; //master_secret
    private static $jpushObj; //推送对象
    private static $log_path; //日志目录
    private static $errorMsg;

    public function __construct()
    {
        //读取配置
        self::$config = config('jpush');
        self::$app_key = self::$config['app_key'];
        self::$master_secret = self::$config['master_secret'];
        self::setLogPath();
        self::$jpushObj = new Client(self::$app_key, self::$master_secret, self::$log_path);
    }

    /**
     * 推送消息给单个用户
     * @param null $alias 推送对象
     * @param null $title 通知标题
     * @param null $desc 推送描述
     * @param array $extras 额外参数
     * @return array|bool
     */
    public function sendToOne($alias = null, $title = null, $desc = null, $extras = array())
    {
        try {
            $result = self::$jpushObj->push()
                ->setPlatform(array('ios', 'android'))
                ->addAlias($alias)
                ->setNotificationAlert($desc)
                ->addAndroidNotification($desc, $title, null, $extras)
                ->addIosNotification($desc, 'default', '+1', true, 'iOS category', $extras)
                //->setMessage($desc, $title, 'type', $extras)
                ->setOptions(100000, 3600, null, true)
                ->send();
            return $result;
        } catch (\Exception $e) {
            self::$errorMsg = $e;
            return false;
        }
    }

    /**
     * 推送消息给全部用户
     * @param null $title 通知标题
     * @param null $desc 推送描述
     * @param null $message 自定义消息
     * @param null $new_type 推送消息的类型
     * @return array|bool
     */
    public function sendToAll($title = null, $desc = null, $message = null, $new_type = null)
    {

        try {
            $result = self::$jpushObj->push()
                ->setPlatform(array('ios', 'android'))
                ->addAllAudience()
                ->setNotificationAlert($title)
                ->addAndroidNotification($desc, $title, null, array("content" => $message, "desc" => $desc))
                ->addIosNotification($title, 'default', '+1', true, 'iOS category', array("content" => $message, "desc" => $desc, "new_type" => $new_type))
                ->setMessage($desc, $title, 'type', array("content" => $message, "desc" => $desc, "new_type" => $new_type))
                ->send();
        } catch (\Exception $e) {
            self::$errorMsg = $e;
            return false;
        }

        return $result;
    }

    /**
     * 创建日志目录
     * @return bool
     */
    public function setLogPath()
    {
        load_helper('File');
        $res = dir_exists(storage_path() . '/jpush');
        if (!$res) {
            return false;
        }
        self::$log_path = storage_path() . '/jpush/jpush-' . date("Y-m-d", time()) . '.log';
        return true;
    }

}