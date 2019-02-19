<?php

namespace JiaLeo\Laravel\Upload;


use App\Exceptions\ApiException;

class ExtCheck
{

    /**
     * 扩展名组
     * @var array
     */
    public static $extGroup = [
        //图片组
        'image' => [
            'jpeg' => 'image/jpeg',
            'jpg' => ['image/jpg', 'image/jpeg'],
            'png' => 'image/png',
            'gif' => 'image/gif',
        ],

        //视频组
        'video' => [
            'mp4' => 'video/mp4',
            'avi' => 'video/avi',
            'mpg' => 'video/mpg',
            '3gp' => ['video/3gp', 'video/3gpp'],
            'rmvb' => 'video/rmvb',
            'wmv' => 'video/wmv',
            'flv' => 'video/x-flv',
            'mkv' => 'video/x-matroska',
            'mov' => 'video/quicktime',
            'ogg' => 'audio/ogg'

        ],

        //音频
        'audio' => [
            'amr' => 'audio/amr'
        ],

        //excel
        'excel' => [
            'xls' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
            'xlsb' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
            'xltm' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
            'xltx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
            'xlsm' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
            'xlam' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']
        ]
    ];

    public static $extLink = [
        //图片类型
        'jpeg' => 'image/jpeg',
        'jpg' => ['image/jpg', 'image/jpeg'],
        'png' => 'image/png',
        'gif' => 'image/gif',

        //视频类型
        'mp4' => 'video/mp4',
        'avi' => 'video/avi',
        'mpg' => 'video/mpg',
        '3gp' => 'video/3gp',
        'rmvb' => 'video/rmvb',
        'wmv' => 'video/wmv',
        'ogg' => 'audio/ogg',

        //音频
        'amr' => 'audio/amr',

        //excel类型
        'xls' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
        'xlsb' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
        'xltm' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
        'xltx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
        'xlsm' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'],
        'xlam' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel']
    ];

    /**
     * 扩展名组验证
     * @param $file_type
     * @param $ext
     * @param string $group_name
     * @return bool
     * @throws ApiException
     */
    public static function extLimitGroup($file_type, $ext, $group_name = 'image')
    {
        if (!isset(self::$extGroup[$group_name])) {
            throw new ApiException('不存在扩展名组验证!');
        }

        $rule = self::$extGroup[$group_name];

        if (!isset($rule[$ext])) {
            return false;
        }

        if (is_array($rule[$ext])) {
            if (in_array($file_type, $rule[$ext])) {
                return true;
            }
        } else {
            if ($file_type != $rule[$ext]) {
                return true;
            }
        }

        return false;
    }

    /**
     * 验证文件类型
     * @param $file_type
     * @param string $group_name
     * @return bool
     * @throws ApiException
     */
    public static function checkFileTypeOfGroup($file_type, $group_name = 'image')
    {
        if (!isset(self::$extGroup[$group_name])) {
            throw new ApiException('不存在扩展名组验证!');
        }

        $rule = self::$extGroup[$group_name];

        foreach ($rule as $v) {
            if (is_array($v)) {
                if (in_array($file_type, $v)) {
                    return true;
                }
            } else {
                if ($file_type == $v) {
                    return true;
                }
            }
        }

        throw new ApiException('不支持的文件类型!');
    }
}