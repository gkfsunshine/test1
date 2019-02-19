<?php
namespace JiaLeo\Laravel\Upload;

use App\Exceptions\ApiException;

class ServersOss
{

    //api部分，调用方法同uploadController的cloud

    /**
     * @var
     */
    public $uploadId;  //数据库model中的id
    public $partNum = 0;   //分块数量
    public $uploadSign = array();  //上传签名列表
    public $goal_url = 'http://10.8.7.207:8020';

    public function __construct()
    {
        //加载配置
        //$this->config = \Config::get('aliyun.oss');
        $this->config = [
            'access_key_id' => 'wxb87e09e1a68a289e',
            'access_key_secret' => '950390bb74b5e65ece1c240a74862b8e',
            'host' => 'http://10.8.7.207:8020/api/files',
            'complete_multipart_url' => $this->goal_url.'/api/complete/multipart',
            'endpoint' => '',
            'sub_dir' => 'test'
        ];
    }


    /**
     * 获取分块id
     * @param \App\Model\UploadModel $upload_model
     * @param $total_size
     * @param $part_size
     * @param $file_type
     * @param $dir
     * @param $filename
     * @param $callback
     * @param bool $is_multi
     * @param string $part_temp_dir
     * @param array $table_field 额外数据表字段
     * @return bool
     * @throws ApiException
     */
    public function getUploadId($upload_model = \App\Model\UploadModel::class, $total_size, $part_size, $file_type, $dir, $filename, $callback, $is_multi = false, $part_temp_dir = 'tempMulti/', $table_field = array())
    {
        $data = compact('total_size', 'part_size', 'file_type', 'filename', 'dir');

        //添加额外字段(array('admin_id'=>1,'user_id'=>1,'store_id'=>1))
        if (!empty($table_field)) {
            foreach ($table_field as $k => $v) {
                $data[$k] = $v;
            }
        }

        $data['dir'] = '/' . ltrim($data['dir'], '/');

        //计算分块数量
        $data['part_num'] = ceil($data['total_size'] / $data['part_size']);
        $data['origin_filename'] = $data['filename'];
        $data['filename'] = md5(time() . rand(10000, 99999) . $data['origin_filename']);
        $data['type'] = 'cloud';

        //分析扩展名
        $ext = pathinfo($data['origin_filename'])['extension'];
        if (empty($ext)) {
            throw new ApiException('文件扩展名获取失败!');
        }

        //完整路径
        $data['path'] = $data['dir'] . $data['filename'] . '.' . $ext;

        //如果是分块且分块数大于1
        if ($is_multi && $data['part_num'] > 1) {
            $data['part_temp_dir'] = trim(trim($this->config['sub_dir'], '/') . '/' . $part_temp_dir, '/');
            $data['is_multi'] = 1;
        } else {
            $data['is_multi'] = 0;
            $data['part_num'] = 1;
        }

        $upload_model = new $upload_model();
        set_save_data($upload_model, $data);
        $upload_model->save();
        $upload_id = $upload_model->id;

        //访问服务器获取上传key
        $key = $this->getUploadKey($is_multi,$data['part_num']);
        if(!$key){
            throw new ApiException('获取upload_key错误!');
        }

        $update_upload = \App\Model\UploadModel::where('id',$upload_id)
            ->update(['key' => $key['key']]);
        if(!$update_upload){
            throw new ApiException('获取upload_key错误!');
        }

        $sign = array();
        if ($is_multi && $data['part_num'] > 1) {
            //生成各块签名
            for ($i = 1; $i <= $data['part_num']; $i++) {
                $temp_sign = $this->getSign($key, $upload_id, $callback, $data['part_temp_dir'] . $upload_id . '/', $data['filename'], $i, true);
                $temp_sign['is_multi'] = 1;
                $temp_sign['type'] = 'cloud';
                $sign[] = $temp_sign;
                unset($temp_sign);
            }
        } else {
            $temp_sign = $this->getSign($key, $upload_id, $callback, $data['dir'], $data['filename'] . '.' . $ext);
            $temp_sign['is_multi'] = 0;
            $temp_sign['type'] = 'cloud';
            $sign[] = $temp_sign;
            unset($temp_sign);
        }

        $this->uploadId = $upload_id;
        $this->partNum = $data['part_num'];
        $this->uploadSign = $sign;

        return true;
    }


    /**
     * 访问服务器获取上传key
     * @param int $is_multi 是否分块
     * @param int $part_num 分块数量
     * @return mixed
     */
    public function getUploadKey($is_multi,$part_num = 1)
    {
        $appid = $this->config['access_key_id'];
        $secret = $this->config['access_key_secret'];

        $param = array();
        load_helper('Network');

        $param['appid'] = $appid;
        $param['secret'] = $secret;
        $param['timestamp'] = time();
        $param['is_multi'] = empty($is_multi)?0:1;
        $param['part_num'] = $part_num;
        $param['sign'] = $this->doSignMd5($param, $secret);

        $string_param = http_build_query($param);

        $res = http_get($this->goal_url . '/api/get/upload/keys?' . $string_param);

        return json_decode($res,true)['data'];
    }

    /**
     * 获取上传加密字符
     * @param string $callbackUrl 回调地址
     * @param string $dir 目录
     * @param string $maxSize 文件最大字节
     * @author: 亮 <chenjialiang@han-zi.cn>
     */
    public function getSign($key, $upload_id, $callbackUrl, $dir = 'temp/', $filename, $part_now = 1, $is_multi = false, $maxSize = 1048576000)
    {
        $access_id = $this->config['access_key_id'];
        $access_key = $this->config['access_key_secret'];
        $host = $this->config['host'];

        if ($is_multi) {
            $filename = $filename . '_' . $part_now;
        }

        $callback_param = array(
            'callbackUrl' => $callbackUrl,
            'callbackBody' => 'object=${object}&size=${size}&mimeType=${mimeType}&height=${imageInfo.height}&width=${imageInfo.width}&filename=' . $filename . '&format=${imageInfo.format}&upload_id=' . $upload_id . '&part_now=' . $part_now,
            'callbackBodyType' => "application/x-www-form-urlencoded");
        $callback_string = json_encode($callback_param);

        $base64_callback_body = base64_encode($callback_string);
        $now = time();
        $expire = 30; //设置该policy超时时间是10s. 即这个policy过了这个有效时间，将不能访问
        $end = $now + $expire;
        $expiration = $this->__gmtIso8601($end);

        //最大文件大小.用户可以自己设置
        $condition = array(0 => 'content-length-range', 1 => 0, 2 => $maxSize);
        $conditions[] = $condition;

        //表示用户上传的数据,必须是以$dir开始, 不然上传会失败,这一步不是必须项,只是为了安全起见,防止用户通过policy上传到别人的目录
        $start = array(0 => 'starts-with', 1 => '$key', 2 => $dir);
        $conditions[] = $start;

        $part_key = [];
        foreach($key['part_key'] as $value){
            if($value['now_part'] == $part_now){
                $part_key = $value;
            }
        }

        $arr = array('expiration' => $expiration, 'conditions' => $conditions, 'key' => $key['key'], 'part_key' => $part_key);
        //echo json_encode($arr);
        //return;
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
     * 上传完成
     * @param $upload_model
     * @param $id
     * @return array
     * @throws ApiException
     */
    public function multiUploadComplete($upload_model, $id)
    {
        //$appid = $this->config['appid'];
        //$secret = $this->config['secret'];
        //$endpoint = $this->config['endpoint'];
        //$bucket = $this->config['bucket'];

        //$ossClient = new OssClient($appid, $secret, $endpoint);

        $field = ['id', 'dir', 'part_num', 'part_now', 'is_multi', 'origin_filename', 'type', 'filename', 'path', 'oss_upload_id', 'oss_part_upload_ids','key'];
        $upload_info = $upload_model::where('is_on', 1)->where('status', 0)->where('id', $id)->first($field);
        if (!$upload_info) {
            throw new ApiException('上传id不符合!');
        }

        if ($upload_info->type != 'cloud') {
            throw new ApiException('上传云id错误!');
        }

        if ($upload_info->part_num != $upload_info->part_now) {
            throw new ApiException('上传还没完成!');
        }

        if ($upload_info->is_multi) {
            $path = trim($upload_info->path, '/');
            $id = $upload_info->id;

            $key = $upload_info->key;

            /*$responseUploadPart = explode(',', $upload_info->oss_part_upload_ids);

            $uploadParts = array();
            foreach ($responseUploadPart as $i => $eTag) {
                $uploadParts[] = array(
                    'PartNumber' => ($i + 1),
                    'ETag' => $eTag,
                );
            }

            $ossClient->completeMultipartUpload($bucket, $path, $upload_id, $uploadParts);*/

            // 访问服务器合成文件
            $this->completeMultipartUpload($path, $key);
        }

        //更新为完成状态
        $update = $upload_model::where('id', $id)->update(['status' => 1]);
        if (!$update) {
            throw new ApiException('上传图片失败!');
        }
        load_helper('File');

        return [
            'url' => file_url($upload_info->path, true),
            'path' => $upload_info->path,
            'origin_filename' => $upload_info->origin_filename
        ];
    }

    /**
     * 请求服务器合成文件
     * @param $path
     * @param $key
     * @return bool|mixed
     */
    public function completeMultipartUpload($path, $key)
    {
        $appid = $this->config['access_key_id'];
        $secret = $this->config['access_key_secret'];

        $param = array();
        load_helper('Network');

        $param['appid'] = $appid;
        $param['secret'] = $secret;
        $param['key'] = $key;
        $param['path'] = $path;
        $param['sign'] = $this->doSignMd5($param, $secret);

        $res = http_post($this->goal_url.'/api/complete/multipart',$param);

        return $res;
    }

    /**
     * 接受回调
     * @author: 亮 <chenjialiang@han-zi.cn>
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
        /*if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
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
        }*/

        $data = request()->request->all();
        $upload_sign = $data['sign'];
        unset($data['sign']);

        $sign = $this->doSignMd5($data, $this->config['access_key_secret']);

        if ($upload_sign !== $sign) {
            throw new ApiException('签名错误!');
        }

        //查询数据库
        $field = ['id', 'dir', 'part_num', 'part_now', 'filename', 'path', 'is_multi', 'oss_upload_id', 'oss_part_upload_ids', 'part_temp_dir'];
        $upload_info = $upload_model::where('is_on', 1)->where('status', 0)->where('id', $data['upload_id'])->first($field);
        if (!$upload_info) {
            throw new ApiException('上传id不符合');
        }

        if ($upload_info->is_multi) {
            if ($upload_info->part_now + 1 != $data['part_now']) {
                throw new ApiException('上传分块错误');
            }

            //复制对象
            $access_id = $this->config['access_key_id'];
            $access_key = $this->config['access_key_secret'];
            $endpoint = $this->config['endpoint'];
            //$bucket = $this->config['bucket'];

            //$ossClient = new OssClient($access_id, $access_key, $endpoint);
            //$object = $upload_info->part_temp_dir . $upload_info->id . '/' . $upload_info->filename . '_' . $data['part_now'];
            //$path = trim($upload_info->path, '/');
            //$time1 = time();
            //$part_upload_id = $ossClient->uploadPartCopy($bucket, $object, $bucket, $path, $data['part_now'], $upload_info->oss_upload_id);
            //$time2 = time();
            //\Log::debug($part_upload_id);
            //\Log::debug($time2 - $time1);
            //组装upload_id
            /*$part_upload_ids = $upload_info->oss_part_upload_ids;
            if (!empty($part_upload_ids)) {
                $part_upload_ids = explode(',', $part_upload_ids);
                //$part_upload_ids[] = $part_upload_id;
                //$oss_part_upload_ids = implode(',', $part_upload_ids);
            } else {
                //$oss_part_upload_ids = $part_upload_id;
            }*/

            $update_data = ['part_now' => $data['part_now']];

        } else {
            $update_data = ['part_now' => 1];
        }

        $update = $upload_model::where('id', $data['upload_id'])->update($update_data);

        //\Log::debug($update);
        if (!$update) {
            throw new ApiException('上传图片失败,请稍后再试!');
        }

        $data = array("Status" => "Ok");

        return $data;
    }

    //请求获取签名前使用的签名
    public function doSignMd5($data, $secret = '')
    {

        //签名步骤一：按字典序排序参数
        ksort($data);
        $string = $this->ToUrlParams($data);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $secret;
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;

    }

    protected function ToUrlParams($data)
    {
        $buff = "";
        foreach ($data as $k => $v) {
            if ($k != "sign" && ($v != "" || $v === 0) && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
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


    // servers部分


    /**
     * 获取uploadKey
     * @return array
     * @throws ApiException
     */
    public function getUploadKye()
    {
        $this->verify([
            'appid' => '',
            'secret' => '',
            'timestamp' => 'timestamp', // 时间戳，目前仅用来加密
            'sign' => '', // api访问servers的签名
            'is_multi' => 'in:0:1',  // 是否分片
            'part_num' => 'num:1:null:true' // 分片数量
        ], 'GET');

        $data = $this->verifyData;

        // image_env是servers的用户表
        $image_env = \App\Model\ImageEnvModel::where('appid', $data['appid'])
            ->select(['secret'])
            ->first();

        if (!$image_env) {
            throw new ApiException('appid错误!');
        }

        if ($image_env['secret'] != $data['secret']) {
            throw new ApiException('secret错误!');
        }

        $param_sign = $data['sign'];
        unset($data['sign']);

        $sign = $this->doSignMd5($data, $image_env['secret']);

        if ($param_sign !== $sign) {
            throw new ApiException('签名错误!');
        }

        $key = md5(time() . str_random(6));

        // 上传数据保存
        $model = new \App\Model\UploadModel();
        $save_data = [
            'appid' => $data['appid'],
            'is_multi' => $data['is_multi'],
            'part_num' => $data['part_num'],
        ];
        set_save_data($model, $save_data);
        $res = $model->save();
        if (!$res) {
            throw new ApiException('数据库错误！');
        }

        // 给每一个分片信息一个指定的KEY，并存缓存
        $data['now_part'] = 1;
        $list = [];
        $key_list = [];
        for ($i = 1; $i <= $data['part_num']; $i++) {
            $part_key = md5(time() . str_random(4));
            $list[] = ['now_part' => $i, 'part_key' => $part_key];
            $key_list[] = $part_key;
        }

        \Cache::add($data['appid'].'.'.$key, ['data' => $data, 'list' => $list], 30);

        return $this->response(['key' => $key, 'part_key' => $list]);
    }


    /**
     * @return mixed
     */
    public function uploadFile()
    {
        $this->verify([
            'key' => '',
            'policy' => '',
            'OSSAccessKeyId' => '',
            'success_action_status' => '',
            'callback' => '',
            'signature' => ''
        ],'POST');

        $data = $this->verifyData;

        // 验证签名
        $image_env = \App\Model\ImageEnvModel::where('appid',$data['OSSAccessKeyId'])
            ->first(['secret']);
        if(!$image_env){
            throw new ApiException('appid错误!');
        }

        $signature = base64_encode(hash_hmac('sha1', $data['policy'], $image_env['secret'], true));

        if ($signature != $data['signature']) {
            throw new ApiException('签名错误！');
        }

        // 验证policy
        $policy_json = base64_decode($data['policy']);

        if (!$policy_json) {
            throw new ApiException('policy有误！');
        }

        $policy = json_decode($policy_json, true);

        if (!$policy) {
            throw new ApiException('policy有误！');
        }

        if (!isset($policy['key'])) {
            throw new ApiException('policy有误！');
        }

        // 获取保存在KEY下的数据
        $key_data = \Cache::get($data['OSSAccessKeyId'] . '.' . $policy['key']);

        if (!$key_data) {
            throw new ApiException('上传失败！');
        }

        if(!isset($policy['part_key']) || !isset($policy['part_key']['now_part']) || !isset($policy['part_key']['part_key'])){
            throw new ApiException('part_key错误！');
        }

        // 验证分块顺序是否有误
        if($key_data['data']['now_part'] !== $policy['part_key']['now_part']){
            throw new ApiException('上传失败！');
        }

        $list = $key_data['list'];
        $now_part_key = '';
        $now_part_data = [];
        $part_key = '';

        // 确定当前分块是第几块，并验证part_key
        foreach($list as $key => $value){
            if($value['now_part'] === $key_data['data']['now_part']){
                $part_key = $value['part_key'];
                $now_part_data = $value;
                $now_part_key = $key;
            }
        }

        if(empty($part_key)){
            throw new ApiException('part_key错误！');
        }

        if($part_key !== $policy['part_key']['part_key']){
            throw new ApiException('part_key错误！');
        }

        $file_path = $data['key'];
        $file_path_arr = pathinfo($file_path);

        $file_name = $file_path_arr['basename']; // 文件名
        $md5_appid = md5($data['OSSAccessKeyId']); // 为每一个appid准备一个专用文件夹

        $dir = '/' . $md5_appid . '/' . rtrim(ltrim($file_path_arr['dirname'], '/'), '/'); // 目录路径

        $path = $dir . '/' . ltrim($file_name, '/'); // 文件路径

        $temp_save_path = public_path() . $path;//临时保存路径

        load_helper('File');
        if (!dir_exists(dirname($temp_save_path))) {
            throw new ApiException('文件保存失败!', 'UPLOAD_ERROR');
        }

        //保存上传的文件
        $move = move_uploaded_file(request()->file('file')->getPathname(), $temp_save_path);
        if (!$move) {
            throw new ApiException('文件保存失败!', 'UPLOAD_ERROR');
        }

        // 回调信息
        if (isset($data['callback']) && !empty($data['callback'])) {

            $return = ['path' => $path, 'key' => $data['key'],'part_now' => $key_data['data']['now_part']];

            load_helper('Network');
            $callback_data = json_decode(base64_decode($data['callback']),true);
            $callback_url = $callback_data['callbackUrl']; // 回调地址
            $callback_body = $callback_data['callbackBody']; // 回调参数
            $callback_body_type = $callback_data['callbackBodyType'];

            parse_str($callback_body,$callback_body_arr);
            $return = array_merge($return, $callback_body_arr);

            $sign = $this->doSignMd5($return, $key_data['data']['secret']); // 回调签名，这个暂用
            $return['sign'] = $sign;

            $res = http_post($callback_url, $return);

            if (!$res || ($res && json_decode($res, true)['Status'] !== 'Ok')) {
                throw new ApiException('上传失败！');
            }
        }


        if($key_data['data']['is_multi'] == 1){
            $now_part_data['path'] = $temp_save_path; // 保存上传成功的地址到缓存
            $list[$now_part_key] = $now_part_data;
            $key_data['list'] = $list;
            $key_data['data']['now_part'] = $key_data['data']['now_part']+1; // 当前分块+1，用于验证下一个分块信息
            \Cache::put($data['OSSAccessKeyId'] . '.' . $policy['key'],$key_data,30); // 将更新后的上传信息覆盖掉原来的缓存
        }else{
            // 成功后删除缓存
            \Cache::delete($data['appid'].'.'.$data['key']);
        }





        return $this->response(true);
    }


    /**
     * 分块合成一
     * @return mixed
     * @throws ApiException
     */
    public function completeMultipart()
    {
        $this->verify([
            'key' => '', // 文件上传的key
            'path' => '', // 合成后的文件路径
            'appid' => '',
            'secret' => '',
            'sign' => ''
        ],'POST');
        $data = $this->verifyData;

        $image_env = \App\Model\ImageEnvModel::where('appid', $data['appid'])
            ->select(['secret'])
            ->first();

        if (!$image_env) {
            throw new ApiException('appid错误!');
        }

        if ($image_env['secret'] != $data['secret']) {
            throw new ApiException('secret错误!');
        }

        $param_sign = $data['sign'];
        unset($data['sign']);

        $sign = $this->doSignMd5($data, $image_env['secret']);

        if ($param_sign !== $sign) {
            throw new ApiException('签名错误!');
        }

        $key_data = \Cache::get($data['appid'].'.'.$data['key']);

        if(!$key_data){
            throw new ApiException('上传失败!');
        }

        $list = $key_data['list'];

        foreach ($list as $value) {
            $part_path = $value['path'];
            $content = file_get_contents($part_path);

            $md5_appid = md5($data['appid']); // 为每一个appid准备一个专用文件夹

            $path = $md5_appid . '/' . ltrim($data['path'], '/'); // 文件路径

            $save_path = public_path() . '/'.$path;//保存路径

            $res = file_put_contents($save_path,$content,FILE_APPEND);

            if(!$res){
                throw new ApiException('上传失败！');
            }
        }

        // 成功后删除缓存
        \Cache::delete($data['appid'].'.'.$data['key']);

        return $this->response(true);
    }
}