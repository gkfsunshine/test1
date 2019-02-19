<?php
namespace JiaLeo\Laravel\Sms;


class MysqlStore implements Contracts\Store
{
    private $errorMsg = '';

    /**
     * 保存
     * @param int $phone
     * @param string $content
     * @param mixed $type
     * @param int $send_result
     * @param string $error_msg
     * @param array $extra_data
     * @return bool
     */
    public function save($phone, $content, $type, $send_result = 1, $error_msg = '', $extra_data = array())
    {

        $this->errorMsg = '';

        $data = [
            'phone' => $phone,
            'content' => $content,
            'type' => $type,
            'send_result' => $send_result,
            'error_msg' => $error_msg,
            'created_at' => time()
        ];
        $data = array_merge($extra_data, $data);
        $res = \DB::table('log_sms')->insert($data);

        if (!$res) {
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