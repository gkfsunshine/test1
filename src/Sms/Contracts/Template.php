<?php

namespace JiaLeo\Laravel\Sms\Contracts;

/**
 * 短信模板接口
 * Interface Template
 * @package JiaLeo\Laravel\Sms\Contracts
 */
interface Template
{

    /**
     * 获取短信模板
     * @return mixed
     */
    public static function getTemplate();

}