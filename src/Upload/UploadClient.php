<?php
namespace JiaLeo\Laravel\Upload;

use App\Exceptions\ApiException;

class UploadClient
{
    /**
     * @var
     */
    public $uploadId;  //数据库model中的id
    public $partNum = 0;   //分块数量
    public $uploadSign = array();  //上传签名列表

    /**
     * @var \JiaLeo\Laravel\Upload\DriverInterface
     */
    public $driver;

    public function __construct()
    {
        //加载配置,默认阿里云
        $driver = config('upload.driver', 'aliyun');
        $class = 'JiaLeo\Laravel\Upload\\' . ucfirst($driver) . 'UploadDriver';
        //实例化驱动
        $this->driver = new $class();
        $this->config = $this->driver->config;
    }

    /**
     * 第一步:获取分块id
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
     * @param bool $generate_filename 是否自动生成文件名
     * @param bool $generate_download_filename 是否设置下载时的文件名(默认为源文件名),false为不设置,true为设置为与文件名相同,传入字符串则为设置为改名字
     * @return bool
     * @throws ApiException
     */
    public function getUploadId($upload_model = \App\Model\UploadModel::class, $total_size, $part_size, $file_type,
                                $dir, $filename, $callback, $is_multi = false, $part_temp_dir = 'tempMulti/',
                                $table_field = array(), $generate_filename = true, $set_download_filename = false)
    {
        $data = compact('total_size', 'part_size', 'file_type', 'filename', 'dir');

        //添加额外字段
        //eg:(array('admin_id'=>1,'user_id'=>1,'store_id'=>1))
        if (!empty($table_field)) {
            foreach ($table_field as $k => $v) {
                $data[$k] = $v;
            }
        }

        if (empty($part_temp_dir)) {
            $part_temp_dir = 'tempMulti/';
        }

        //上传目录
        $data['dir'] = ltrim(trim($this->config['sub_dir'], '/') . '/' . $data['dir'], '/');

        //计算分块数量
        $data['part_num'] = ceil($data['total_size'] / $data['part_size']);
        $data['origin_filename'] = $data['filename'];

        //文件名,判断是否生成文件名
        if ($generate_filename) {
            $data['filename'] = md5(time() . rand(10000, 99999) . $data['origin_filename']);
        } else {
            $data['filename'] = pathinfo($data['origin_filename'])['filename'];
        }

        //上传类型
        $data['type'] = 'cloud';
        $data['cloud_type'] = $this->driver->driver_name;

        //分析扩展名
        $ext = pathinfo($data['origin_filename'])['extension'];
        if (empty($ext)) {
            throw new ApiException('文件扩展名获取失败!');
        }

        //完整路径
        $data['path'] = '/' . $data['dir'] . $data['filename'] . '.' . $ext;

        $options = array();
        $download_filename = '';
        if ($set_download_filename === true) {              //true的时候使用filename
            $download_filename = $data['filename'];
        } elseif ($set_download_filename !== false) {       //使用传值作为文件名
            $download_filename = $set_download_filename;
        }

        if ($set_download_filename) {
            $options['set_download_file'] = $download_filename;
        }


        //如果是分块且分块数大于1
        if ($is_multi && $data['part_num'] > 1) {

            $res = $this->driver->initiateMultipartUpload($data['path'], $part_temp_dir, $options);
            $data['oss_upload_id'] = $res['oss_upload_id'];
            $data['part_temp_dir'] = $res['part_temp_dir'];
            $data['is_multi'] = 1;
        } else {
            $data['is_multi'] = 0;
            $data['part_num'] = 1;
        }

        //上传记录保存到数据库
        $upload_model = new $upload_model();
        set_save_data($upload_model, $data);
        $res = $upload_model->save();
        if (!$res) {
            throw new ApiException('数据库错误,请稍后再试!');
        }
        $upload_id = $upload_model->id;

        //生成签名
        $sign = array();
        if ($is_multi && $data['part_num'] > 1) {
            //生成各块签名
            for ($i = 1; $i <= $data['part_num']; $i++) {

                //计算文件限制大小
                if ($i == $data['part_num']) {
                    $file_size = $data['total_size'] - $data['part_size'] * ($data['part_num'] - 1);
                } else {
                    $file_size = $data['part_size'];
                }

                $temp_sign = $this->driver->getSign($upload_id, $callback, $data['part_temp_dir'] . '/', $data['filename'], $i, true, $file_size);
                $temp_sign['is_multi'] = 1;
                $temp_sign['type'] = 'cloud';
                $temp_sign['origin_filename'] = $data['origin_filename'];
                $temp_sign['fixed_download_filename'] = $download_filename;
                $sign[] = $temp_sign;
                unset($temp_sign);
            }
        } else {
            $temp_sign = $this->driver->getSign($upload_id, $callback, $data['dir'], $data['filename'] . '.' . $ext, 1, false, (int)$data['total_size']);
            $temp_sign['is_multi'] = 0;
            $temp_sign['type'] = 'cloud';
            $temp_sign['origin_filename'] = $data['origin_filename'];
            $temp_sign['fixed_download_filename'] = $download_filename;

            $sign[] = $temp_sign;
            unset($temp_sign);
        }

        $this->uploadId = $upload_id;
        $this->partNum = $data['part_num'];
        $this->uploadSign = $sign;

        return true;
    }

    /**
     * 第二步:接受回调
     * @param string $upload_model
     * @return array
     * @throws ApiException
     */
    public function notify($upload_model)
    {

        return $this->driver->notify($upload_model);
    }

    /**
     * 第三步:上传完成
     * @param $upload_model
     * @param $id
     * @return array
     * @throws ApiException
     */
    public function uploadComplete($upload_model, $id)
    {
        $field = ['id', 'dir', 'total_size', 'part_size', 'part_num', 'part_now', 'is_multi', 'origin_filename', 'type',
            'status', 'cloud_type', 'filename', 'path', 'oss_upload_id', 'oss_part_upload_ids', 'part_temp_dir'];
        $upload_info = $upload_model::where('is_on', 1)
            ->select($field)
            ->find($id);

        if (!$upload_info) {
            throw new ApiException('上传id不符合!');
        }

        if ($upload_info->status != 0) {
            throw new ApiException('上传已完成!');
        }

        if ($upload_info->type != 'cloud') {
            throw new ApiException('上传云id错误!');
        }

        //检查是否上传完毕
        $this->driver->checkIsFinishUplaod($upload_info);

        if ($upload_info->is_multi) {

            //复制文件为云上分块
            $this->driver->copyFileToMultiPart($upload_info);

            //请求完成分块文件
            $path = trim($upload_info->path, '/');
            $id = $upload_info->id;

            $upload_id = $upload_info->oss_upload_id;

            $responseUploadPart = explode(',', $upload_info->oss_part_upload_ids);

            $uploadParts = array();
            foreach ($responseUploadPart as $i => $eTag) {
                $uploadParts[] = array(
                    'PartNumber' => ($i + 1),
                    'ETag' => $eTag,
                );
            }
            $this->driver->completeMultipartUpload($path, $upload_id, $uploadParts);

            //删除分块文件
            $files = array();
            for ($i = 0; $i < $upload_info->part_num; $i++) {
                $files[] = $upload_info->part_temp_dir . '/' . $upload_info->filename . '_' . ($i + 1);
            }
            $this->driver->deleteTempFile($files);
        }

        //更新为完成状态
        //$update = $upload_model::where('id', $id)->update(['status' => 1]);
        $upload_info->status = 1;
        $update = $upload_info->save();
        if (!$update) {
            throw new ApiException('数据库错误,请稍后再试!');
        }
        load_helper('File');

        return [
            'url' => file_url($upload_info->path, true),
            'path' => $upload_info->path,
            'origin_filename' => $upload_info->origin_filename
        ];
    }

    /**
     * 分块上传文件
     * @param $object
     * @param $file_path
     * @return string
     */
    public function multiuploadFile($object, $file_path, $options = NULL)
    {
        $config = $this->config;
        if (!empty($config['sub_dir'])) {
            $object = trim($config['sub_dir'], '/') . '/' . $object;
        }

        $set_options = NULL;

        if (isset($options['set_download_file'])) {
            $set_options['headers'] = array(
                'Content-Disposition' => 'attachment;filename=' . $options['set_download_file']
            );
        }

        $this->driver->multiuploadFile($object, $file_path, $set_options);

        return $object;
    }

}