<?php
namespace JiaLeo\Laravel\Sms;


class FileStore implements Contracts\Store
{

    public $errorMsg;

    /**
     * 保存
     * @param int $phone
     * @param string $content
     * @param mixed $type
     * @param bool $send_result
     * @param string $error_msg
     * @param array $extra_data
     * @return bool
     */
    public function save($phone, $content, $type, $send_result = true, $error_msg = '', $extra_data = array())
    {
        $this->errorMsg = '';

        load_helper('File');
        $res = dir_exists(storage_path() . '/sms');
        if (!$res) {
            $this->errorMsg = '生成记录日志目录失败！';
            return false;
        }
        $path = storage_path() . '/sms/sms-' . date("Y-m-d", time()) . '.log';
        $source = PHP_EOL . PHP_EOL . date("Y-m-d H:i:s", time()) . ' --------------------------' . PHP_EOL .
            '发送结果:' . (!$send_result ? '失败' : '成功') . PHP_EOL .
            '错误信息:' . $error_msg . PHP_EOL .
            '电话：' . $phone . PHP_EOL .
            '发送内容：' . $content . PHP_EOL;

        if (!empty($extra_data)) {
            $source .= '额外记录字段:' . json_encode($extra_data, JSON_UNESCAPED_UNICODE) . PHP_EOL;;
        }

        if (!file_put_contents($path, $source, FILE_APPEND)) {
            $this->errorMsg = '记录日志错误！';
            return false;
        }

        return true;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getErrorMsg()
    {
        return $this->errorMsg;
    }

}