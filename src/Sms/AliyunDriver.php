<?php

namespace JiaLeo\Laravel\Sms;

class AliyunDriver implements Contracts\Driver
{
    private $config;        //配置
    private $errorMsg;      //错误信息

    //需要提示用户的错误信息
    private $tip_error = [
        //黑名单管控
        [
            'code' => 'isv.BLACK_KEY_CONTROL_LIMIT',
            'message' => '手机号码可能是黑名单或内容涉及敏感信息，请联系服务商。'
        ],
        //业务停机
        [
            'code' => 'isv.OUT_OF_SERVICE',
            'message' => '应用提供方短信余额不足，请联系服务商。'
        ],
        //非法手机号
        [
            'code' => 'isv.MOBILE_NUMBER_ILLEGAL',
            'message' => '请输入正确的国内手机号。'
        ],
        //不支持URL
        [
            'code' => 'isv.PARAM_NOT_SUPPORT_URL',
            'message' => '内容涉及敏感信息，请联系服务商。'
        ],
        //账户余额不足
        [
            'code' => 'isv.AMOUNT_NOT_ENOUGH',
            'message' => '应用提供方短信余额不足，请联系服务商。'
        ],
        //模板变量里包含非法关键字
        [
            'code' => 'isv.TEMPLATE_PARAMS_ILLEGAL',
            'message' => '内容涉及敏感信息，请联系服务商。'
        ],

    ];

    /**
     * AliyunDriver constructor.
     * @param $config
     */
    public function __construct($config)
    {
        //读取配置
        $this->config = array(
            'accessKeyId' => $config['aliyun']['app_key'],
            'accessSecret' => $config['aliyun']['app_secret'],
            'sign_name' => $config['aliyun']['sign_name'],
        );

    }

    /**
     * 发送操作
     * @param int $phone 手机号码
     * @param string $template_code 模板代码
     * @param array $param 模板参数
     * @param array $save_data 日志保存额外字段
     * @return bool
     */
    public function send($phone, $template_code, $params = array(), $extra_data = array())
    {
        //发送
        $params = array(
            'AccessKeyId' => $this->config['accessKeyId'],
            'Timestamp' => date('Y-m-d\TH:i:s\Z', strtotime("-8 hour")),
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce' => time() . rand(10000, 99999),
            'Format' => 'JSON',

            'Action' => 'SendSms',
            'Version' => '2017-05-25',
            'RegionId' => 'cn-hangzhou',
            'PhoneNumbers' => $phone,
            'SignName' => $this->config['sign_name'],
            'TemplateCode' => $template_code,
            'TemplateParam' => json_encode($params)
        );

        ksort($params);

        $new_params = array();
        foreach ($params as $key => $v) {
            $new_params[$this->tranStr($key)] = $this->tranStr($v);
        }

        $new_str = '';
        foreach ($new_params as $key => $v) {
            $new_str .= '&' . $key . '=' . $v;
        }
        $new_str = trim($new_str, '&');
        $sign_str = 'POST&' . $this->tranStr('/') . '&' . $this->tranStr($new_str);

        $sign = base64_encode(hash_hmac('sha1', $sign_str, $this->config['accessSecret'] . '&', true));

        $params['Signature'] = $sign;
        ksort($params);

        load_helper('Network');

        $url = 'http://dysmsapi.aliyuncs.com';
        $reponse = http_post($url, $params);

        if ($reponse !== FALSE) {
            $res = json_decode($reponse, TRUE);

            if (isset($res['Code']) && $res['Code'] == 'OK') {
                return TRUE;
            }

            $this->errorMsg = $res;
            foreach ($this->tip_error as $error) {
                if ($res['Code'] == $error['code']) {
                    return $error['message'];
                }
            }
        } else {
            $this->errorMsg = array('code' => 0, 'msg' => 'HTTP_RESPONSE_NOT_WELL_FORMED');
        }

        return false;
    }

    /**
     * 转换字符串
     * @param $str
     * @return mixed|string
     */
    public function tranStr($str)
    {
        $str = urlencode($str);
        $str = preg_replace('/\+/', '%20', $str);
        $str = preg_replace('/\*/', '%2A', $str);
        $str = preg_replace('/%7E/', '~', $str);

        return $str;
    }
    

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }




}