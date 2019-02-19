<?php
namespace JiaLeo\Laravel\Upload;

interface DriverInterface
{

    public function getSign($upload_id, $callbackUrl, $dir, $filename, $part_now, $is_multi, $maxSize);

    public function notify($model);

    public function completeMultipartUpload($path, $upload_id, $uploadParts);

    public function initiateMultipartUpload($path, $part_temp_dir, $options = array());
}