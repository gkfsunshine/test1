<?php
namespace JiaLeo\Laravel\Sms;

class AlidayuDriver implements Contracts\Driver
{

    private $config;
    private $_setting = array();
    private $errorMsg = array();

    /**
     * AlidayuDriver constructor.
     * @param $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     *
     * @param int $phone
     * @param string $template_code
     * @param array $param
     * @return bool
     */
    public function send($phone, $template_code, $params = array(), $extra_data = array())
    {
        $this->_setting['sms_param'] = json_encode($params);
        $this->_setting['sms_template_code'] = $template_code;
        $this->_setting['rec_num'] = (string)$phone;
        $params = $this->_params();
        $params['sign'] = $this->_signed($params);
        $reponse = $this->_curl($params);

        if ($reponse !== FALSE) {
            $res = json_decode($reponse, TRUE);
            $res = array_pop($res);

            if (isset($res['result'])) {
                return TRUE;
            }

            $this->errorMsg = $res;
        } else {
            $this->errorMsg = array('code' => 0, 'msg' => 'HTTP_RESPONSE_NOT_WELL_FORMED');
        }
        return FALSE;
    }

    private function _params()
    {
        return array
        (
            'sms_free_sign_name' => $this->config['alidayu']['sign_name'],
            'app_key' => $this->config['alidayu']['app_key'],
            'format' => 'json',
            'method' => 'alibaba.aliqin.fc.sms.num.send',
            'v' => '2.0',
            'timestamp' => date('Y-m-d H:i:s'),
            'sign_method' => 'md5',
            'sms_type' => 'normal',
        ) + $this->_setting;
    }

    private function _signed($params)
    {
        ksort($params);
        $sign = $this->config['alidayu']['app_secret'];
        foreach ($params as $k => $v) {
            if (is_string($v) && '@' != substr($v, 0, 1)) $sign .= $k . $v;
        }
        $sign .= $this->config['alidayu']['app_secret'];
        return strtoupper(md5($sign));
    }

    private function _curl($params)
    {
        $uri = 'https://eco.taobao.com/router/rest?' . http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_URL, $uri);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Verydows');
        $reponse = curl_exec($ch);
        curl_close($ch);
        return $reponse;
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