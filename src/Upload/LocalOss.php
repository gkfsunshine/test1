<?php
namespace JiaLeo\Laravel\Upload;

use App\Exceptions\ApiException;
use OSS\OssClient;
use OSS\Core\OssException;

class LocalOss
{

    public $uploadId;  //数据库model中的id
    public $partNum = 0;   //分块数量
    public $uploadSign = array();  //上传签名列表

    public function __construct()
    {

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
    public function getUploadId($upload_model = \App\Model\UploadModel::class, $total_size, $part_size, $file_type, $dir, $filename,
                                $upload_host, $is_multi = false, $part_temp_dir = '', $table_field = array(),
                                $generate_filename = true, $set_download_filename = false)
    {
        $data = compact('total_size', 'part_size', 'file_type', 'filename', 'dir', 'path_temp_dir');

        //添加额外字段(array('admin_id'=>1,'user_id'=>1,'store_id'=>1))
        if (!empty($table_field)) {
            foreach ($table_field as $k => $v) {
                $data[$k] = $v;
            }
        }

        //计算分块数量
        $data['part_num'] = ceil($data['total_size'] / $data['part_size']);
        $data['origin_filename'] = $data['filename'];
        if ($generate_filename) {
            $data['filename'] = md5(time() . rand(10000, 99999) . $data['origin_filename']);
        } else {
            $data['filename'] = pathinfo($data['origin_filename'])['filename'];
        }
        $data['type'] = 'local';

        //分析扩展名
        $ext = pathinfo($data['origin_filename'])['extension'];
        if (empty($ext)) {
            throw new ApiException('文件扩展名获取失败!');
        }

        //完整路径
        $data['path'] = '/' . $data['dir'] . $data['filename'] . '.' . $ext;

        $data['part_temp_dir'] = 'multiupload/' . date('Ymd') . '/' . $part_temp_dir;

        //如果是分块且分块数大于1
        if ($is_multi && $data['part_num'] > 1) {
            $data['is_multi'] = 1;
        } else {
            $data['is_multi'] = 0;
            $data['part_num'] = 1;
        }

        $upload_model = new $upload_model();
        set_save_data($upload_model, $data);
        $upload_model->save();
        $upload_id = $upload_model->id;

        $options = array();
        $download_filename = '';
        if ($set_download_filename === true) {              //true的时候使用filename
            $download_filename = $data['filename'] . '.' . $ext;
        } elseif ($set_download_filename !== false) {       //使用传值作为文件名
            $download_filename = $set_download_filename;
        }

        if ($set_download_filename) {
            $options['set_download_file'] = $download_filename;
        }

        $sign = array();
        if ($is_multi && $data['part_num'] > 1) {
            //生成各块签名
            for ($i = 1; $i <= $data['part_num']; $i++) {
                $temp_sign['is_multi'] = 1;
                $temp_sign['type'] = 'local';
                $temp_sign['host'] = $upload_host;
                $temp_sign['upload_id'] = $upload_id;
                $temp_sign['part_now'] = $i;
                $temp_sign['fixed_download_filename'] = $download_filename;
                $sign[] = $temp_sign;
                unset($temp_sign);
            }
        } else {
            $temp_sign['is_multi'] = 0;
            $temp_sign['type'] = 'local';
            $temp_sign['host'] = $upload_host;
            $temp_sign['upload_id'] = $upload_id;
            $temp_sign['part_now'] = 1;
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
     * 本地上传(块)
     * @param $upload_model
     * @param $upload_id
     * @param $part_now
     * @param bool $is_upload 是否上传到云
     * @param string $local_save_dir 本地上传基础目录
     * @return bool
     * @throws ApiException
     */
    public function updatePart($upload_model = \App\Model\UploadModel::class, $upload_id, $part_now, $is_upload = false, $local_save_dir = '', $set_download_filename = false)
    {
        //查询数据库
        $field = ['id', 'dir', 'part_num', 'part_now', 'filename', 'path', 'type', 'is_multi', 'oss_upload_id',
            'oss_part_upload_ids', 'part_temp_dir'];

        $upload_info = $upload_model::where('is_on', 1)
            ->where('status', 0)
            ->where('id', $upload_id)
            ->first($field);

        if (!$upload_info) {
            throw new ApiException('上传id不符合', 'UPLOAD_ERROR');
        }

        if ($upload_info->type != 'local') {
            throw new ApiException('上传本地id不符合', 'UPLOAD_ERROR');
        }

        //上传的分块顺序错误
        if ($upload_info->part_now + 1 != $part_now) {
            throw new ApiException('上传分块错误', 'UPLOAD_ERROR');
        }

        //临时保存路径
        $temp_save_path = storage_path() . '/' . $upload_info->part_temp_dir . $upload_info->filename . '_' . $part_now;

        load_helper('File');
        if (!dir_exists(dirname($temp_save_path))) {
            throw new ApiException('文件保存失败!', 'UPLOAD_ERROR');
        }

        if (empty($local_save_dir)) {
            $local_save_dir = public_path();
        } else {
            if (!dir_exists(dirname($local_save_dir))) {
                throw new ApiException('本地保存路径错误!', 'UPLOAD_ERROR');
            }
        }

        //保存上传的文件
        $move = move_uploaded_file(request()->file('file')->getPathname(), $temp_save_path);
        if (!$move) {
            throw new ApiException('文件保存失败!', 'UPLOAD_ERROR');
        }

        if ($upload_info->is_multi) {
            $update_data = ['part_now' => $part_now];
        } else {
            $update_data = ['part_now' => 1];
        }

        //已完成上传
        if ($upload_info->part_num == $part_now) {
            $update_data['is_cloud'] = intval($is_upload);

            $file_path = storage_path() . '/' . $upload_info->part_temp_dir . $upload_info->filename . '_1';
            $fp = fopen($file_path, "a+");

            //多分块的合并文件
            for ($i = 1; $i <= $upload_info->part_num; $i++) {
                if ($i == 1) {
                    continue;
                }

                $get_path = storage_path() . '/' . $upload_info->part_temp_dir . $upload_info->filename . '_' . $i;
                $fp2 = fopen($get_path, "r");
                $content = fread($fp2, filesize($get_path));
                fwrite($fp, $content);
                fclose($fp2);

                //删除分块
                unlink($get_path);
            }
            fclose($fp);

            $options = array();
            if ($set_download_filename !== false) {       //使用传值作为文件名
                $options['set_download_file'] = $set_download_filename;
            }

            //判断是否上传到云盘
            if (!$is_upload) {
                $save_path = $local_save_dir . $upload_info->path;
                if (!dir_exists(dirname($save_path))) {
                    throw new ApiException('保存文件失败!', 'UPLOAD_ERROR');
                }
                $is_rename = rename($file_path, $save_path);
                if (!$is_rename) {
                    throw new ApiException('保存文件失败!', 'UPLOAD_ERROR');
                }
            } else {

                $object = ltrim($upload_info->path, '/');

                load_helper('File');
                $path = upload_to_cloud($file_path, $object, true, $options);

                //更新数据库中的路径
                $update_data['path'] = '/' . $path;

            }
        }

        $update = $upload_model::where('id', $upload_id)->update($update_data);
        if (!$update) {
            throw new ApiException('数据库错误,请稍后再试!');
        }
        return true;
    }

    /**
     * 分块上传完成
     * @param string $path 文件路径
     * @param int $part_num 分块数量
     * @param string $part_filename 分块文件名
     * @param string string $temp_path 分块临时路径
     * @return string $upload_id
     * @throws OssException
     */
    public function multiUploadComplete($upload_model, $id)
    {
        $field = ['id', 'dir', 'part_num', 'part_now', 'origin_filename', 'filename', 'path', 'type', 'is_cloud', 'oss_upload_id', 'oss_part_upload_ids'];
        $upload_info = $upload_model::where('is_on', 1)->where('status', 0)->where('id', $id)->first($field);
        if (!$upload_info) {
            throw new ApiException('上传id不符合!', 'UPLOAD_ERROR');
        }

        if ($upload_info->type != 'local') {
            throw new ApiException('上传本地id不符合!', 'UPLOAD_ERROR');
        }

        if ($upload_info->part_num != $upload_info->part_now) {
            throw new ApiException('上传还没完成!', 'UPLOAD_ERROR');
        }

        //更新为完成状态
        $update = $upload_model::where('id', $id)->update(['status' => 1]);
        if (!$update) {
            throw new ApiException('上传图片失败!');
        }
        load_helper('File');

        //是否在云盘中
        $is_cloud = boolval($upload_info->is_cloud);

        return [
            'url' => file_url($upload_info->path, $is_cloud),
            'path' => $upload_info->path,
            'origin_filename' => $upload_info->origin_filename
        ];
    }

}