<?php
namespace JiaLeo\Laravel\Signature;

use App\Exceptions\ApiException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;

class MysqlStorage implements Storage
{
    private static $table = 'access_key';

    public function __construct()
    {
        if(!\Schema::hasTable(self::$table)){
            throw new ApiException('不存在access_key表');
        }
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
        $access_key = \DB::table(self::$table)
            ->where('is_on', 1)
            ->where('access_key_id', $access_key_id)
            ->where('access_key_secret', $access_key_secret)
            ->first();
        if (empty($access_key)) {
            $res = \DB::table(self::$table)->insert([
                'access_key_id' => $access_key_id,
                'access_key_secret' => $access_key_secret
            ]);
            if (!$res) {
                return false;
            }
        }
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
        $access_key = \DB::table(self::$table)
            ->where('access_key_id', $access_key_id)
            ->first();
        return empty($access_key)?'':$access_key->access_key_secret;
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
        $res = \DB::table(self::$table)
            ->where('access_key_id', $access_key_id)
            ->delete();
        return $res;
    }

    /**
     * 删除所有的数据
     * 如果数据删除成功返回true，否则返回false
     * @return bool
     */
    public function deleteAll()
    {
        $res = \DB::table(self::$table)
            ->delete();
        return $res;
    }


}