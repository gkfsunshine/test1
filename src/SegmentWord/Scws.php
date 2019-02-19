<?php
namespace JiaLeo\Laravel\SegmentWord;

class Scws
{

    public $scws;

    public function __construct($config = array())
    {
        $scws = scws_new();
        $this->scws = $scws;

        //默认字符集
        $this->scws->set_charset(!isset($config['charset']) ? 'utf8' : $config['charset']);

        //设定词典路径
        $this->scws->set_rule(!isset($config['rule']) ? ini_get('scws.default.fpath') . '/rules_cht.utf8.ini' : $config['rule']);

        //设定词典路径
        $this->scws->set_dict(!isset($config['dict']) ? ini_get('scws.default.fpath') . '/dict.utf8.xdb' : $config['dict']);

        //设定是否将闲散文字自动以二字分词法聚合
        $this->scws->set_duality(!isset($config['set_duality']) ? false : boolval($config['set_duality']));

        //去除标点符号
        $this->scws->set_ignore(!isset($config['set_ignore']) ? true : boolval($config['set_ignore']));

        //返回结果时是否复式分割，如“中国人”返回“中国＋人＋中国人”三个词。
        $this->scws->set_multi(!isset($config['set_multi']) ? true : boolval($config['set_multi']));

    }

    /**
     * 发送结果
     * @param $str
     * @return mixed
     */
    public function sendText($str)
    {
        return $this->scws->send_text($str);
    }

    /**
     * 获取分词结果
     * @return array
     */
    public function getResult()
    {
        $words = array();
        while ($res = $this->scws->get_result()) {
            $words = array_merge($words, $res);
        }
        return $words;
    }


    /**
     * 返回系统计算出来的最关键词汇列表
     * @return mixed
     */
    public function getTops()
    {
        return $this->scws->get_tops(5);
    }


}