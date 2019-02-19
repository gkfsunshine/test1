<?php
namespace JiaLeo\Laravel\Upload;

use App\Exceptions\ApiException;
use App\Model\UploadModel;
use Guzzle\Common\Collection;
use Guzzle\Http\EntityBody;
use Qcloud\Cos\MultipartUpload;

class QcloudUploadDriver implements DriverInterface
{
    public $driver_name = 'qcould';

    public $config;
    public $client;

    public function __construct()
    {
        $this->config = config('qcloud.cos');

        if (empty($this->config)) {
            throw new ApiException('请设置上传配置!');
        }

        $this->client = new \Qcloud\Cos\Client(array('region' => $this->config['region'],
            'credentials' => array(
                'appId' => $this->config['appid'],
                'secretId' => $this->config['access_key_id'],
                'secretKey' => $this->config['access_key_secret']
            )
        ));
    }

    /**
     * 获取上传加密字符
     * @param int $upload_id uplaod表id
     * @param string $callbackUrl 回调url
     * @param string $dir 上传目录
     * @param string $filename 文件名
     * @param int $part_now 当前上传块
     * @param bool $is_multi 是否分块
     * @param int $maxSize 最大大小
     * @return array
     */
    public function getSign($upload_id, $callbackUrl, $dir = 'temp/', $filename, $part_now = 1, $is_multi = false, $maxSize = 1048576000)
    {

        if ($is_multi) {
            throw new ApiException('直传到COS暂不支持分块上传,请联系管理员~');
        }

        $access_id = $this->config['access_key_id'];
        $access_key = $this->config['access_key_secret'];
        $host = $this->config['host'];

        if ($is_multi) {
            $filename = $filename . '_' . $part_now;
        }

        $now = time();
        $expire = 30 * 60;              //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->__gmtIso8601($end);

        //最大文件大小.用户可以自己设置
        $condition = array(0 => 'content-length-range', 1 => 0, 2 => $maxSize);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;

        $arr = array('expiration' => $expiration, 'conditions' => $conditions);

        $policy = json_encode($arr);
        $base64_policy = base64_encode($policy);

        $authorization = $this->getAuthorization('POST', '/');

        $response = array();
        $response['accessid'] = $access_id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $authorization;
        $response['expire'] = $end;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;
        $response['filename'] = $filename;
        $response['guid'] = $filename;
        $response['size'] = $maxSize;

        return $response;
    }

    /**
     * 初始化分块上传
     * @param $path
     * @param $part_temp_dir
     * @return mixed
     */
    public function initiateMultipartUpload($path, $part_temp_dir)
    {

        $multi_path = trim(trim($this->config['sub_dir'], '/') . '/' . $path, '/');

        $result = $this->client->createMultipartUpload(array(
            'Bucket' => $this->config['bucket'],
            'Key' => $multi_path,
        ));
        $data['oss_upload_id'] = $result['UploadId'];
        $data['part_temp_dir'] = trim(trim($this->config['sub_dir'], '/') . '/' . $part_temp_dir, '/');
        return $data;
    }

    /**
     * 接受回调
     * @param string $upload_model
     * @return array
     * @throws ApiException
     */
    public function notify($upload_model)
    {
        return true;
    }

    /**
     * 检查文件是否已上传完
     */
    public function checkIsFinishUplaod(UploadModel $info)
    {

        if ($info->is_multi) {
            $prefix = $info->part_temp_dir . '/' . $info->filename;
        } else {
            $prefix = trim($info->path, '/');
        }

        $res = $this->client->listObjects(array(
            'Bucket' => $this->config['bucket'],
            'Prefix' => $prefix
        ));

        $list = $res->getAll();
        if (!empty($list['Contents'])) {
            if (count($list['Contents']) != $info->part_num) {
                throw new ApiException('上传还没有完成!');
            }

            //判断每一块的大小
            foreach ($list['Contents'] as $key => $v) {
                if ($key != $info->part_num - 1) {
                    $require_size = $info->part_size;
                } else {
                    $require_size = $info->total_size - $info->part_size * ($info->part_num - 1);
                }

                if ($require_size != $v['Size']) {
                    throw new ApiException('上传数据有误,请重试!');
                }
            }
        }
    }

    public function copyFileToMultiPart(UploadModel $info)
    {

        for ($i = 1; $i <= $info->part_num; $i++) {
            $temp_filename = $info->part_temp_dir . '/' . $info->filename . '_' . $i;

            $array = array(
                'Bucket' => $this->config['bucket'],
                'CopySource' => str_replace('https://', '', $this->config['host'] . '/' . $temp_filename),
                'PartNumber' => $i,
                'UploadId' => $info->oss_upload_id,
                'Key' => ltrim($info->path, '/'),
            );

            var_dump($array);
            $res = $this->client->UploadPartCopy($array);

            var_dump($res);
        }


    }

    /**
     * 完成分块上传
     * @param $path
     * @param $upload_id
     * @param $uploadParts
     */
    public function completeMultipartUpload($path, $upload_id, $uploadParts)
    {

        $this->client->completeMultipartUpload(array(
            'Bucket' => $this->config['bucket'],
            'Key' => $path,
            'UploadId' => $upload_id,
            'Parts' => $uploadParts
        ));
    }

    /**
     * 分块上传
     * @param $object
     * @param $file_path
     * @throws ApiException
     */
    public function multiuploadFile($object, $file_path)
    {
        $body = fopen($file_path, 'rb');
        $options = array();
        $body = EntityBody::factory($body);
        $options = Collection::fromConfig(array_change_key_case($options), array(
            'min_part_size' => MultipartUpload::MIN_PART_SIZE,
            'params' => $options));
        if ($body->getSize() < $options['min_part_size']) {
            // Perform a simple PutObject operation
            $rt = $this->client->putObject(array(
                    'Bucket' => $this->config['bucket'],
                    'Key' => $object,
                    'Body' => $body,
                ) + $options['params']);

            $rt['Location'] = $rt['ObjectURL'];
            unset($rt['ObjectURL']);
        } else {
            $multipartUpload = new MultipartUpload($this->client, $body, $options['min_part_size'], array(
                    'Bucket' => $this->config['bucket'],
                    'Key' => $object,
                    'Body' => $body,
                ) + $options['params']);

            $rt = $multipartUpload->performUploading();
        }
    }

    /**
     * 删除分块临时文件(实体)
     * @param $files
     */
    public function deleteTempFile($files)
    {
        return $this->client->deleteObjects($this->config['bucket'], $files);
    }

    // 工具方法

    /**
     * 获取POST方法的签名
     * @param $method
     * @param $pathname
     * @return string
     */
    private function getAuthorization($method, $pathname)
    {

        // 获取个人 API 密钥 https://console.qcloud.com/capi
        $SecretId = $this->config['access_key_id'];
        $SecretKey = $this->config['access_key_secret'];
        // 整理参数
        $query = array();
        $headers = array();
        $method = strtolower($method ? $method : 'get');
        $pathname = $pathname ? $pathname : '/';
        substr($pathname, 0, 1) != '/' && ($pathname = '/' . $pathname);

        // 签名有效起止时间
        $now = time() - 1;
        $expired = $now + 600; // 签名过期时刻，600 秒后
        // 要用到的 Authorization 参数列表
        $qSignAlgorithm = 'sha1';
        $qAk = $SecretId;
        $qSignTime = $now . ';' . $expired;
        $qKeyTime = $now . ';' . $expired;
        $qHeaderList = strtolower(implode(';', $this->getObjectKeys($headers)));
        $qUrlParamList = strtolower(implode(';', $this->getObjectKeys($query)));
        // 签名算法说明文档：https://www.qcloud.com/document/product/436/7778
        // 步骤一：计算 SignKey
        $signKey = hash_hmac("sha1", $qKeyTime, $SecretKey);
        // 步骤二：构成 FormatString
        $formatString = implode("\n", array(strtolower($method), $pathname, $this->obj2str($query), $this->obj2str($headers), ''));
        //header('x-test-method', $method);
        //header('x-test-pathname', $pathname);
        // 步骤三：计算 StringToSign
        $stringToSign = implode("\n", array('sha1', $qSignTime, sha1($formatString), ''));
        // 步骤四：计算 Signature
        $qSignature = hash_hmac('sha1', $stringToSign, $signKey);
        // 步骤五：构造 Authorization
        $authorization = implode('&', array(
            'q-sign-algorithm=' . $qSignAlgorithm,
            'q-ak=' . $qAk,
            'q-sign-time=' . $qSignTime,
            'q-key-time=' . $qKeyTime,
            'q-header-list=' . $qHeaderList,
            'q-url-param-list=' . $qUrlParamList,
            'q-signature=' . $qSignature
        ));
        return $authorization;
    }


    private function __gmtIso8601($time)
    {
        $dtStr = date("c", $time);
        $mydatetime = new \DateTime($dtStr);
        $expiration = $mydatetime->format(\DateTime::ISO8601);
        $pos = strpos($expiration, '+');
        $expiration = substr($expiration, 0, $pos);
        return $expiration . "Z";
    }

    private function getObjectKeys($obj)
    {
        $list = array_keys($obj);
        sort($list);
        return $list;
    }

    private function obj2str($obj)
    {
        $list = array();
        $keyList = $this->getObjectKeys($obj);
        $len = count($keyList);
        for ($i = 0; $i < $len; $i++) {
            $key = $keyList[$i];
            $val = isset($obj[$key]) ? $obj[$key] : '';
            $key = strtolower($key);
            $list[] = rawurlencode($key) . '=' . rawurlencode($val);
        }
        return implode('&', $list);
    }
}