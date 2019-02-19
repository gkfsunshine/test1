<?php
namespace JiaLeo\Laravel\Sms;


class MysqlTemplate implements Contracts\Template
{

    /**
     * 获取短信模板
     * @return array(
     *          type => [
     *              'template_code' => 'SMS_129763325',
     *              'content' => '您正在进行注册操作，验证码为：${code}，5分钟内有效，请勿泄漏给他人！',
     *              ]
     *         )
     */
    public static function getTemplate()
    {

        $sms_template = \Cache::get('sms_template', function () {
            $sms_template = [];
            $list = \App\Model\SmsTemplateModel::where('is_on', 1)->get();
            foreach ($list as $key => $value) {
                $sms_template[$value['log_type']] = [
                    'template_code' => $value['template_code'],
                    'content' => $value['content'],
                    'type' => $value['type'],
                ];
            }
            \Cache::put('sms_template', $sms_template, 60);
            return $sms_template;
        });

        return $sms_template;
    }

}