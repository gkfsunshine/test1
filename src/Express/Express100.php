<?php
namespace JiaLeo\Laravel\Express;

/**
 * Express.php 快递100 类
 */
class Express100
{
    protected $_CI;

    const API_URL = 'http://poll.kuaidi100.com';
    const POLL_URL = '/poll';

    public $saller;
    public $key;

    public $errCode;
    public $errMsg;

    public function __construct()
    {
        $config = \Config::get('express.express100');
        $this->saller = $config['saller'];
        $this->key = $config['key'];
    }

    /**
     * 订阅请求
     * @param $number int 运单号
     * @param $company string 快递公司编号
     * @param $mobiletelephone string 通知人手机
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function poll($number, $company, $callback_url, $mobiletelephone = null)
    {
        $param = array(
            'company' => $company,
            'number' => $number,
            'key' => $this->key,
            'parameters' => array(
                'callbackurl' => $callback_url,
                'mobiletelephone' => $mobiletelephone,
                'seller' => $this->saller,
            )
        );
        $data = array(
            'schema' => 'json',
            'param' => json_encode($param)
        );

        load_helper('Network');
        $result = http_get(self::API_URL . self::POLL_URL . '?' . http_build_query($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || $json['result'] != true) {
                $this->errCode = $json['returnCode'];
                $this->errMsg = $json['message'];
                return false;
            } else
                if (isset($json)) return $json;
        }

        return false;
    }

    /**
     * 接受回调
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function callback()
    {
        $param = request()->request->get('param');

        if (empty($param)) {
            return false;
        }

        $json = json_decode($param, true);
        if (!$json) {
            return false;
        }

        return $json;
    }

}