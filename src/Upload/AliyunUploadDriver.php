<?php

namespace JiaLeo\Laravel\Upload;

use App\Exceptions\ApiException;
use App\Model\UploadModel;
use OSS\Core\OssException;
use OSS\OssClient;

class AliyunUploadDriver implements DriverInterface
{
    public $driver_name = 'aliyun';

    public $config;
    private $client;

    public function __construct()
    {
        $this->config = config('aliyun.oss');
        if (empty($this->config)) {
            throw new ApiException('请设置上传配置!');
        }

        $this->client = new OssClient($this->config['access_key_id'], $this->config['access_key_secret'], $this->config['endpoint']);
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

        $access_id = $this->config['access_key_id'];
        $access_key = $this->config['access_key_secret'];
        $host = $this->config['host'];

        if ($is_multi) {
            $filename = $filename . '_' . $part_now;
        }

        //回调参数
        $callback_param = array(
            'callbackUrl' => $callbackUrl,
            'callbackBody' => 'object=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}&filename=' . $filename . '&format=${imageInfo.format}&upload_id=' . $upload_id . '&part_now=' . $part_now,
            'callbackBodyType' => "application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);
        $base64_callback_body = base64_encode($callback_string);

        //实测好像这个过期时间无效
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
        $string_to_sign = $base64_policy;
        $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $access_key, true));

        $response = array();
        $response['accessid'] = $access_id;
        $response['host'] = $host;
        $response['policy'] = $base64_policy;
        $response['signature'] = $signature;
        $response['expire'] = $end;
        $response['callback'] = $base64_callback_body;
        //这个参数是设置用户上传指定的前缀
        $response['dir'] = $dir;
        $response['filename'] = $filename;
        $response['guid'] = $filename;

        return $response;
    }


    /**
     * 初始化分块上传
     * @param $path
     * @param $part_temp_dir
     * @return mixed
     */
    public function initiateMultipartUpload($path, $part_temp_dir, $set_options = array())
    {
        $multi_path = trim($path, '/');

        $options = array();

        if (isset($set_options['set_download_file'])) {
            $options['headers'] = array(
                'Content-Disposition' => 'attachment;filename=' . $set_options['set_download_file']
            );
        }

        //获取一个upload_id
        $data['oss_upload_id'] = $this->client->initiateMultipartUpload($this->config['bucket'], $multi_path, $options);
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
        // 1.获取OSS的签名header和公钥url header
        $authorizationBase64 = "";
        $pubKeyUrlBase64 = "";
        /*
         * 注意：如果要使用HTTP_AUTHORIZATION头，你需要先在apache或者nginx中设置rewrite，以apache为例，修改
         * 配置文件/etc/httpd/conf/httpd.conf(以你的apache安装路径为准)，在DirectoryIndex index.php这行下面增加以下两行
            RewriteEngine On
            RewriteRule .* - [env=HTTP_AUTHORIZATION:%{HTTP:Authorization},last]
         * */
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authorizationBase64 = $_SERVER['HTTP_AUTHORIZATION'];
        }
        if (isset($_SERVER['HTTP_X_OSS_PUB_KEY_URL'])) {
            $pubKeyUrlBase64 = $_SERVER['HTTP_X_OSS_PUB_KEY_URL'];
        }

        if ($authorizationBase64 == '' || $pubKeyUrlBase64 == '') {
            header("http/1.1 403 Forbidden");
            exit();
        }

        // 2.获取OSS的签名
        $authorization = base64_decode($authorizationBase64);

        // 3.获取公钥
        $pubKeyUrl = base64_decode($pubKeyUrlBase64);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pubKeyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        $pubKey = curl_exec($ch);
        if ($pubKey == "") {
            //header("http/1.1 403 Forbidden");
            exit();
        }

        // 4.获取回调body
        $body = file_get_contents('php://input');

        // 5.拼接待签名字符串
        $authStr = '';
        $path = $_SERVER['REQUEST_URI'];
        $pos = strpos($path, '?');
        if ($pos === false) {
            $authStr = urldecode($path) . "\n" . $body;
        } else {
            $authStr = urldecode(substr($path, 0, $pos)) . substr($path, $pos, strlen($path) - $pos) . "\n" . $body;
        }

        // 6.验证签名
        $ok = openssl_verify($authStr, $authorization, $pubKey, OPENSSL_ALGO_MD5);
        if (!$ok) {
            throw new ApiException('Authorization_Fail');
        }

        $data = request()->request->all();

        //查询数据库
        $field = ['id', 'dir', 'part_num', 'part_now', 'part_size', 'file_type', 'total_size', 'filename', 'path', 'status',
            'is_multi', 'oss_upload_id', 'oss_part_upload_ids', 'part_temp_dir'];
        $upload_info = $upload_model::where('is_on', 1)
            ->select($field)
            ->find($data['upload_id']);
        if (!$upload_info) {
            throw new ApiException('上传id不符合');
        }

        if ($upload_info->status != 0) {
            throw new ApiException('上传已完成!');
        }

        if ($upload_info->is_multi) {
            if ($upload_info->part_now + 1 != $data['part_now']) {
                throw new ApiException('上传分块错误');
            }

            //验证文件大小
            if ($data['part_now'] != $upload_info->part_num) {
                $size = $upload_info->part_size;
            } else {
                $size = $upload_info->total_size - $upload_info->part_size * ($upload_info->part_num - 1);
            }

            if ($data['size'] != $size) {
                throw new ApiException('文件大小验证错误!');
            }

            //复制对象
            $access_id = $this->config['access_key_id'];
            $access_key = $this->config['access_key_secret'];
            $endpoint = $this->config['endpoint'];
            $bucket = $this->config['bucket'];

            $ossClient = new OssClient($access_id, $access_key, $endpoint);
            //$object = $upload_info->part_temp_dir . $upload_info->id . '/' . $upload_info->filename . '_' . $data['part_now'];
            $object = $data['object'];
            $path = trim($upload_info->path, '/');
            $part_upload_id = $ossClient->uploadPartCopy($bucket, $object, $bucket, $path, $data['part_now'], $upload_info->oss_upload_id);

            //组装upload_id
            $part_upload_ids = $upload_info->oss_part_upload_ids;
            if (!empty($part_upload_ids)) {
                $part_upload_ids = explode(',', $part_upload_ids);
                $part_upload_ids[] = $part_upload_id;
                $oss_part_upload_ids = implode(',', $part_upload_ids);
            } else {
                $oss_part_upload_ids = $part_upload_id;
            }

            $update_data = ['part_now' => $data['part_now'], 'oss_part_upload_ids' => $oss_part_upload_ids];

        } else {

            //验证文件大小
            if ($data['size'] != $upload_info->total_size) {
                throw new ApiException('文件大小验证错误!');
            }

            //验证文件类型
            if ($data['mimeType'] != $upload_info->file_type) {
                throw new ApiException('文件类型验证错误!');
            }

            $update_data = ['part_now' => 1];
        }

        $update = $upload_model::where('id', $data['upload_id'])->update($update_data);

        if (!$update) {
            throw new ApiException('上传图片失败,请稍后再试!');
        }

        $data = array("Status" => "Ok");
        return $data;
    }

    /**
     * 检查文件是否已上传完
     */
    public function checkIsFinishUplaod(UploadModel $info)
    {
        if ($info->part_num != $info->part_now) {
            throw new ApiException('上传还没完成!');
        }
    }

    public function copyFileToMultiPart(UploadModel $info)
    {
        return true;
    }

    /**
     * 完成分块上传
     * @param $path
     * @param $upload_id
     * @param $uploadParts
     * @throws OssException
     */
    public function completeMultipartUpload($path, $upload_id, $uploadParts)
    {
        $this->client->completeMultipartUpload($this->config['bucket'], $path, $upload_id, $uploadParts);
    }

    /**
     * 分块上传
     * @param $object
     * @param $file_path
     * @throws ApiException
     */
    public function multiuploadFile($object, $file_path, $options = NULL)
    {
        try {
            $this->client->multiuploadFile($this->config['bucket'], $object, $file_path, $options);
        } catch (OssException $e) {
            throw new ApiException('上传失败!', 'UPLOAD_ERROR');
        }
    }

    /**
     * 删除分块临时文件(实体)
     * @param $files
     * @return \OSS\Http\ResponseCore
     * @throws OssException
     */
    public function deleteTempFile($files)
    {
        return $this->client->deleteObjects($this->config['bucket'], $files);
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
}