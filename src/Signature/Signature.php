<?php

namespace JiaLeo\Laravel\Signature;

use App\Exceptions\ApiException;

class Signature
{
    private $storage;
    private $config;

    public function __construct(Storage $storage, $config)
    {
        $this->config = $config;
        $this->storage = $storage;
    }

    /**
     * 生成签名
     * @param $values ,$raw,access_key_id
     * @return string
     */
    public function makeSign($values, $access_key_id, $bodyText = '')
    {

        if (empty($this->storage)) {
            return false;
        }
        $access_key_secret = $this->storage->retrieve($access_key_id);
        //签名步骤一：按字典序排序参数
        ksort($values);
        $string = self::toUrlParams($values);
        //签名步骤二：在string后加入KEY
        $string = $string . $bodyText . "&key=" . $access_key_secret;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    private static function toUrlParams($values)
    {
        $buff = "";
        foreach ($values as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }

    /*
     * 检测签名是否正确
     */
    public function checkSign()
    {
        $params = \Request::all();
        if (!isset($params['timestamp']) || empty($params['timestamp']) || !is_numeric($params['timestamp'])) {
            throw new ApiException('签名timestamp不存在或不合法');
        }
        if (abs($params['timestamp'] - time()) >= 15 * 60) {
            throw new ApiException('签名timestamp错误');
        }
        //检测签名信息是否存在
        if ($this->config['signature_from'] == 'header') {
            \Log::info(\Request::header('Authorization'));
            $signature = \Request::header($this->config['signature_name']);
        } elseif ($this->config['signature_from'] == 'body') {
            $signature = $params($this->config['signature_name']);
        } else {
            throw new ApiException('签名携带位置配置不合法');
        }

        if (empty($signature)) {
            throw new ApiException('没有携带签名' . $this->config['signature_name']);
        }
        //检测access_key_id是否存在
        if ($this->config['id_from'] == 'body') {
            $access_key_id = \Request::input($this->config['id_name']);
        } elseif ($this->config['id_from'] == 'header') {
            $access_key_id = \Request::header($this->config['id_name']);
        } else {
            throw new ApiException('access_key_id携带位置配置不合法');
        }
        if (empty($access_key_id)) {
            throw new ApiException('没有携带签名id');
        }
        //如果POST的数据以TEXT的形式传递的话也要把TEXT数据进行签名
        $signature_result = $this->makeSign($params, $access_key_id,(empty($_POST)&&!empty(file_get_contents('php://input')))?file_get_contents('php://input'):'');
        if ($signature_result != $signature) {
            throw new ApiException('签名错误');
        }
        return true;
    }

    /*
     * 生成access_key
     * return boolean
     */
    public function createAccessKey()
    {
        $access_key_id = str_random('16');
        $access_key_secret = str_random(random_int(16, 32));
        $res = $this->storage->persist($access_key_id, $access_key_secret);
        if (!$res) {
            return false;
        }
        return ['access_key_id' => $access_key_id, 'access_key_secret' => $access_key_secret];
    }


}