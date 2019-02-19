<?php
namespace JiaLeo\Laravel\Sms\Contracts;

/**
 * 短信储存接口
 * Interface Store
 * @package JiaLeo\Laravel\Sms\Contracts
 */
interface Store
{

    /**
     * 保存
     * @param $phone int 手机号码
     * @param $content string 短信正文
     * @param $type mixed 类型
     * @param $send_result bool 是否发送成功
     * @param $error_msg string 错误信息
     * @param $extra_data array 额外信息
     * @return mixed
     */
    public function save($phone, $content, $type, $send_result = true, $error_msg = '', $extra_data = array());

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getErrorMsg();

}