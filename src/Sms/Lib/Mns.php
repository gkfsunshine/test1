<?php

namespace JiaLeo\Laravel\Sms\Lib;

use AliyunMNS\Client;
use AliyunMNS\Topic;
use AliyunMNS\Constants;
use AliyunMNS\Model\MailAttributes;
use AliyunMNS\Model\SmsAttributes;
use AliyunMNS\Model\BatchSmsAttributes;
use AliyunMNS\Model\MessageAttributes;
use AliyunMNS\Exception\MnsException;
use AliyunMNS\Requests\PublishMessageRequest;


class Mns
{

    private $config;
    private $client;
    public $error='';

    public function __construct()
    {
        $config = config('sms.alidayu');

        $this->config = array(
            'accessId' => $config['app_key'],        //'LTAIyPMY23fh5TE3',
            'accessKey' => $config['app_secret'],    //'5ihCBVGszRFkViLmIyGezPsG0NEFFF',
            'signName' => $config['sign_name'],
            'endPoint' => $config['end_point'],
            'topicName' => $config['topic_name']
        );

        $this->client = new Client($this->config['endPoint'], $this->config['accessId'], $this->config['accessKey']);
    }

    /**
     * 发送短信
     * @param $phone
     * @param $template_code
     * @param $params
     * @return bool
     */

    /**
     * @param $phone
     * @param $template_code
     * @param $params
     * @return bool
     */
    public function send($phone, $template_code, $params)
    {

        /**
         * Step 2. 获取主题引用
         */
        $topic = $this->client->getTopicRef($this->config['topicName']);

        /**
         * Step 3. 生成SMS消息属性
         */
        // 3.1 设置发送短信的签名（SMSSignName）和模板（SMSTemplateCode）
        $batchSmsAttributes = new BatchSmsAttributes($this->config['signName'], $template_code);

        // 3.2 （如果在短信模板中定义了参数）指定短信模板中对应参数的值
        $batchSmsAttributes->addReceiver($phone, $params);
        $messageAttributes = new MessageAttributes(array($batchSmsAttributes));

        /**
         * Step 4. 设置SMS消息体（必须）
         *
         * 注：目前暂时不支持消息内容为空，需要指定消息内容，不为空即可。
         */
        $messageBody = "smsmessage";

        /**
         * Step 5. 发布SMS消息
         */
        $request = new PublishMessageRequest($messageBody, $messageAttributes);
        try
        {
            $res = $topic->publishMessage($request);

            if($res->isSucceed()){
                return true;
            }
        } catch (MnsException $e)
        {
            $this->error =$e;
            return false;
        }

        return true;
    }


}