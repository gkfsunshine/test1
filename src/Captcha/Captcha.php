<?php
namespace JiaLeo\Laravel\Captcha;

use Gregwar\Captcha\CaptchaBuilder;

class Captcha
{

    private $width = 100;
    private $height = 40;
    private $font = null;
    private $isDigital = false; //是否未纯位数
    private $limit = 4; //位数
    private $code = null; //验证码
    private $charset = 'abcdefghijklmnpqrstuvwxyz123456789'; //随机字符串

    /**
     * 创建验证码,浏览器直接输出
     * @param $name string 验证码名称
     * @param $timeout int 有效时间--分钟
     */
    public function create($name = 'register', $timeout = 1)
    {
        $code = $this->createCode();

        //把内容存入session
        $value = array(
            'code' => $code,
            'expires_time' => time() + $timeout * 60
        );

        \Jwt::set($name, $value);

        $this->createImg($code);
    }

    /**
     * 生成验证码图片
     * @param $code string
     */
    public function createImg($code = '')
    {

        $this->code = $code;

        if (empty($code)) {
            $this->createCode();
        }

        $builder = new CaptchaBuilder($this->code);
        //可以设置图片宽高及字体
        $builder->build($this->width, $this->height, $this->font = null);

        //生成图片
        header("Cache-Control: no-cache, must-revalidate");
        header('Content-Type: image/jpeg');
        $builder->output();
        exit;
    }

    /**
     *  生成验证码
     */
    public function createCode()
    {
        if (!empty($this->code)) {
            return $this->code;
        }

        //生成验证码图片的Builder对象，配置相应属性
        if ($this->isDigital) {
            $this->code = $this->generateMsgAuthCode($this->limit); //纯验证码
        } else {
            $this->code = $this->buildCode(); //验证码
        }

        return $this->code;
    }

    /**
     * 验证验证码
     * @param $key
     * @return bool
     */
    public function checkCodeInfo($name, $code)
    {
        $value = \Jwt::get($name);

        if (!$value) {
            return false;
        }

        if (!isset($value['expires_time']) || !isset($value['code'])) {
            return false;
        }

        if ($value['code'] != $code) {
            return false;
        }

        if ($value['expires_time'] < time()) {
            return false;
        }
        \Jwt::delete($name);
        return true;
    }

    /**
     * 获取验证码信息
     * @return mixed
     */
    public function getCodeInfo($name)
    {
        $value = \Jwt::get($name);
        if (!$value) {
            return false;
        }

        return $value;
    }

    /**
     * 设置验证码
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * 设置验证码
     * @return mixed
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * 设置宽度
     * @param $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * 设置高度
     * @param $height
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * 设置字体
     * @param $font
     * @return $this
     */
    public function setFont($font)
    {
        $this->font = $font;
        return $this;
    }

    /**
     * 设置是否纯数字
     * @param $isDigital
     * @return $this
     */
    public function setDigital($isDigital)
    {
        $this->isDigital = $isDigital;
        return $this;
    }


    /**
     * 设置位数
     * @param $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * 设置字典
     * @param $charset
     * @return $this
     */
    public function setCharset($charset)
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * 生成数字验证码
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function generateMsgAuthCode($limit)
    {
        $rand_array = range(0, 9);
        shuffle($rand_array); //调用现成的数组随机排列函数
        return implode('', array_slice($rand_array, 0, $limit)); //截取前$limit个
    }

    /**
     * 生成验证码
     * @author 伟健
     */
    public function buildCode()
    {
        $phrase = '';
        $chars = str_split($this->charset);

        for ($i = 0; $i < $this->limit; $i++) {
            $phrase .= $chars[array_rand($chars)];
        }

        return $phrase;
    }

    /**
     *  重置参数
     */
    public function reset()
    {
        $this->width = 100;
        $this->heigh = 40;
        $this->font = null;
        $this->isDigital = false;
        $this->limit = 4;
        $this->code = null;
    }

}