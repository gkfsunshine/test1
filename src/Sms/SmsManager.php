<?php

namespace JiaLeo\Laravel\Sms;

use App\Exceptions\ApiException;

class SmsManager
{
    private $config;        //短信配置
    private $logStore;      //日志存储示例

    private $code;          // 验证码
    private $type = null;   // 短信类型
    private $driverObj;     // 驱动示例化对象

    public $errorMsg;

    /**
     * Create a new Sms manager instance.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct($config, Contracts\Store $log_store)
    {
        $this->config = $config;
        $this->logStore = $log_store;
    }


    /**
     * 设置为验证码
     * @return $this
     */
    public function setcode($length = 6)
    {
        //生成随机验证码
        $this->code = $this->generateMsgAuthCode($length);
        return $this;
    }

    /**
     * 设置短信类型
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置为验证验证码
     * @param string $name 验证码名称
     * @param int $phone 验证手机
     * @param array $data 额外写入内容
     * @param int $minute 分钟
     * @return bool
     * @throws ApiException
     */
    public function setCaptcha($name, $phone, $data = array(), $minute = 10)
    {
        if (empty($this->code)) {
            throw new ApiException('你还没有设置验证码!');
        }

        $data = array(
            'phone' => $phone
        );
        $set_name = 'sms_check:' . $name;
        \JiaLeo\Laravel\Captcha\Custom::setCaptcha($set_name, $this->code, $data, $minute);

        return true;
    }

    /**
     * 验证验证码是否正确
     * @param string $name 验证码名称
     * @param int $phone 手机号码
     * @param int $code 验证码
     * @return array|bool
     * @throws ApiException
     */
    public function checkCaptcha($name, $phone, $code)
    {
        $check_name = 'sms_check:' . $name;
        $extra_verfiy = array(
            'phone' => $phone  //验证手机号
        );
        $get_code = \JiaLeo\Laravel\Captcha\Custom::checkCaptcha($check_name, $code, $extra_verfiy);

        return $get_code;
    }

    /**
     * 删除验证码
     * @param $name
     * @return array|bool
     */
    public function deleteCaptcha($name)
    {
        $delete_name = 'sms_check:' . $name;
        return \JiaLeo\Laravel\Captcha\Custom::deleteCaptcha($delete_name);
    }

    /**
     * 获取验证码
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

    /**
     * 生成验证码
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function generateMsgAuthCode($length)
    {
        return \JiaLeo\Laravel\Captcha\Custom::generateMsgAuthCode($length);
    }

    /**
     * 重置短信类
     */
    public function reset()
    {
        $this->code = '';
        $this->errorMsg = '';
        $this->type = null;
    }

    /**
     * 发送操作
     * @param int $phone 手机号码
     * @param string $template_code 模板代码
     * @param array $param 模板参数
     * @param array $save_data 日志保存额外字段
     * @return bool
     */
    public function send($phone, $template_code, $template_param = array(), $save_data = array(), $extra_data = array())
    {
        //如果有额外参数
        if (!empty($template_param)) {
            $template_param['templet'] = $template_code;
        }
        //如果验证码不为空,则发送验证码(验证码字段为code)
        if (!empty($this->code)) {
            $template_param['code'] = $this->code;

        }
        //兼容旧版本
        if (is_numeric($template_code)) {
            $info = $this->config['templet'][$template_code];
            $this->type = $template_code;
            $template_code = $info['template_code'];
            $template_type = $info['type'] ?? 'verification';
        } else {
            $template_type = 'verification';  //notification
        }
        $pass_flag = true;

        // 如果开启限流
        $is_check_limit = $this->config['limit']['is_on'] ?? false;
        if ($is_check_limit && $template_type == 'verification') {

            try {
                $this->limitCheck($phone);
            } catch (\Exception $e) {
                $send_result = 0;
                $error_msg = '限流策略-错误信息' . $e->getMessage();
                $this->errorMsg = $e->getMessage();
                $pass_flag = false;
            }
        }

        if ($pass_flag) {
            if (isset($this->config['is_queue']) && $this->config['is_queue'] == true) {
                dispatch((new \App\Jobs\SmsSend($phone, $template_code, $template_param, $extra_data, $save_data, $this->type))->onQueue('sms_send'));
                return true;
            }
            //发送
            $res = $this->getDriver()->send($phone, $template_code, $template_param, $extra_data);

            //发送结果
            if ($res === true) {
                $this->errorMsg = '';
                $send_result = 1;
            } else {
                $send_result = 0;
                if (!$res) {
                    $this->errorMsg = '短信发送错误,请稍后再试!';

                } else {
                    $this->errorMsg = $res;
                }
            }

            $error_msg = $res ? 'success' : json_encode($this->getDriver()->getErrorMsg(), JSON_UNESCAPED_UNICODE);
        }
        //添加日志
        $this->addLog($phone, $template_code, $template_param, $save_data, $send_result, $error_msg);

        //重置
        unset($this->driverObj);
        $this->driverObj = null;

        if (!$pass_flag || !$send_result) {
            return false;
        }

        return true;
    }

    /**
     * 判断流量限制
     * @param $phone
     * @return bool
     * @throws ApiException
     */
    public function limitCheck($phone)
    {

        if (!(isset($this->config['limit']) && $this->config['limit']['is_on'])) {
            return true;
        }

        $time = time();
        $list = \Cache::get('SMS_' . $phone);

        if (!empty($list)) {
            $collect = collect($list);
            $num = $collect->where('created_at', '>=', $time - 60)->count();

            if ($num >= $this->config['limit']['limit_per_minute']) {
                throw new ApiException('发送短信过于频繁,请1分钟后重试!');
            }

            $num = $collect->where('created_at', '>=', $time - 3600)->count();
            if ($num >= $this->config['limit']['limit_per_hour']) {
                throw new ApiException('发送短信过于频繁,请1小时后重试!');
            }

            $num = $collect->where('created_at', '>=', $time - 86400)->count();
            if ($num >= $this->config['limit']['limit_per_day']) {
                throw new ApiException('发送短信过于频繁,请1天后重试!');
            }
            $list[] = ['created_at' => $time];

        } else {
            $list[] = ['created_at' => $time];
        }
        \Cache::put('SMS_' . $phone, $list, 60 * 24);
        return true;
    }

    /**
     * Get a sms driver instance.
     *
     * @param  string $driver
     * @return \JiaLeo\Laravel\Sms\Contracts\Driver
     */
    public function getDriver()
    {
        if ($this->driverObj) {
            return $this->driverObj;
        }

        //获取驱动
        $driver = isset($this->config['driver']) ? $this->config['driver'] : $this->config['run'];

        $driver_class = 'JiaLeo\Laravel\Sms\\' . ucfirst($driver) . 'Driver';

        if (!class_exists($driver_class)) {
            throw new ApiException("Driver [$driver] is not supported.");
        }

        $this->driverObj = new $driver_class($this->config);

        return $this->driverObj;
    }

    /**
     * 添加日志
     * @param int $phone 手机号码
     * @param string $template_code 模板代码
     * @param array $param 模板参数
     * @param array $extra_data 日志保存额外字段
     */
    public function addLog($phone, $template_code, $template_param, $extra_data, $send_result = 1, $error_msg = 'success')
    {
        if (!$this->config['create_log']) {
            return true;
        }
        if (empty($this->type)) {
            //发送内容
            $templet = $this->config['templet'][$template_code];
            if (is_array($templet)) {
                $content = $templet['content'];
                if (isset($templet['type'])) {
                    $type = $templet['type'];
                } else {
                    $type = $template_code;
                }

            } else {
                $content = $templet;
                $type = 0;
            }
        } else {
            $templet = $this->config['templet'][$this->type];
            if (is_array($templet)) {
                $content = $templet['content'];
            } else {
                $content = $templet;
            }
            $type = $this->type;
        }

        //替换模板里的参数
        if (!empty($template_param)) {
//            if($this->config['run'] == 'tencent'){ // 腾讯云
//                foreach ($template_param as $k => $v) {
//                    if (is_numeric($k)) {
//                        $index = $k + 1;
//                        $content = str_replace('${' . $index . '}', $v, $content);//替换字符串
//                    } else {
//                        $content = str_replace('${' . $k . '}', $v, $content);//替换字符串
//                    }
//                    $content = str_replace('${' . $k . '}', $v, $content);//替换字符串
//                }
//            }else{
//                foreach ($template_param as $k => $v) {
//                    $content = str_replace('${' . $k . '}', $v, $content);//替换字符串
//                }
//            }
            foreach ($template_param as $k => $v) {
                $content = str_replace('${' . $k . '}', $v, $content);//替换字符串
            }
        }

        $save_result = $this->logStore->save($phone, $content, $type, $send_result, $error_msg, $extra_data);
        if (!$save_result) {
            \Log::error('短信记录失败!错误信息:' . $this->logStore->getErrorMsg());
        }

        return true;
    }


}