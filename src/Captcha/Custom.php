<?php
namespace JiaLeo\Laravel\Captcha;

use App\Exceptions\ApiException;

/**
 * 自定义验证码类
 * Class Custom
 * @package JiaLeo\Laravel\Captcha
 */
class Custom
{

    /**
     * 设置验证码
     * @param string $name 验证码名称
     * @param array $data 额外写入内容
     * @param int $minute 分钟
     * @return string $code
     * @throws ApiException
     */
    public static function setCaptcha($name, $code = '', $data = array(), $minute = 10)
    {
        if (empty($code)) {
            $code = self::generateMsgAuthCode(6);
        }

        $sms_check_data = array(
            'msgcode' => $code,
            'expires_time' => time() + 60 * $minute   //xx分钟有效期
        );

        $data = array_merge($data, $sms_check_data);

        \Jwt::set($name, $data);

        return $code;
    }

    /**
     * 验证验证码是否正确
     * @param string $name 验证码名称
     * @param int $code 验证码
     * @param array $extra_verfiy 额外需要验证的字段 key=>value
     * @return array|bool
     * @throws ApiException
     */
    public static function checkCaptcha($name, $code, $extra_verfiy = array())
    {
        //尝试获取验证码
        $get_code = \Jwt::get($name);
        if (empty($get_code)) {
            throw new ApiException('请重新获取验证码!');
        }

        if (!empty($extra_verfiy)) {
            foreach ($extra_verfiy as $key => $v) {
                if ($get_code[$key] != $v) {
                    throw new ApiException('请重新获取验证码!');
                }
            }
        }

        //判读验证码是否正确
        if ($get_code['msgcode'] != $code) {
            throw new ApiException('验证码错误!');
        }

        //判断是否过期
        if ($get_code['expires_time'] < time()) {
            throw new ApiException('验证码已过期,请重新获取验证码!');
        }

        return $get_code;
    }

    /**
     * 删除验证码
     * @param $name
     * @return array|bool
     */
    public static function deleteCaptcha($name)
    {
        return \Jwt::delete($name);
    }


    /**
     * 生成验证码
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public static function generateMsgAuthCode($limit)
    {
        $rand_array = range(0, 9);
        shuffle($rand_array); //调用现成的数组随机排列函数
        return implode('', array_slice($rand_array, 0, $limit)); //截取前$limit个
    }

}