<?php
namespace JiaLeo\Laravel\Signature;


class RedisStorage implements Storage
{
    private $redis;


    /*
     * 依赖predis
     */
    public function __construct(\Predis\Client $client)
    {
        $this->redis = $client;
    }

    /**
     * 持久化数据方法
     * 返回新创建的对象ID
     *
     * @param array () $data
     * @return boolean
     */
    public function persist($access_key_id, $access_key_secret)
    {
        $this->redis->hset('auth_storage', $access_key_id, $access_key_secret);
        return true;
    }

    /**
     * 通过指定access_key_id返回数据
     * 如果为空返回null
     *
     * @param string $access_key_id
     * @return string
     */
    public function retrieve($access_key_id)
    {
        return $this->redis->hget('auth_storage', $access_key_id);
    }

    /**
     * 通过指定id删除数据
     * 如果数据不存在返回false，否则如果删除成功返回true
     *
     * @param string $access_key_id
     * @return bool
     */
    public function delete($access_key_id)
    {
        return $this->redis->hdel('auth_storage', [$access_key_id]);

    }

    /**
     * 删除所有的数据
     * 如果数据删除成功返回true，否则返回false
     * @return bool
     */
    public function deleteAll()
    {
        return $this->redis->del(['auth_storage']);
    }
}