<?php

namespace JiaLeo\Laravel\Sms\Contracts;

/**
 * 短信驱动接口
 * Interface Driver
 * @package JiaLeo\Laravel\Sms\Contracts
 */
interface Driver
{

    /**
     * 发送操作
     * @param int $phone 手机号码
     * @param string $template_code 模板代码
     * @param array $param 模板参数
     * @param array $save_data 日志保存额外字段
     * @return bool
     */
    public function send($phone, $template_code, $template_param = array(), $extra_data = array());

    /**
     * 获取错误信息
     * @return mixed
     */
    public function getErrorMsg();

}