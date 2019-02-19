<?php
namespace JiaLeo\Laravel\Core\Helper;


class HelperInstance
{

    /**
     * 实例
     * @var array
     */
    private $__helperInstance = array();

    public function __construct()
    {
        // 初始化加载核心辅助函数
        $this->load_helper('Helper');
    }

    /**
     * 加载helper
     * @param $helper_name
     * @return bool
     */
    public function load_helper($helper_name)
    {

        $helper_name = ucfirst($helper_name);

        if (isset($this->__helperInstance[$helper_name])) {
            return true;
        }

        // 先加载系统的辅助函数
        if (!file_exists(__DIR__ . '/' . $helper_name . '.php')) {

            // 再加载App目录下heler文件夹
            if (!file_exists(app_path('Helper') . '/' . $helper_name . '.php')) {
                return false;
            }

            require_once app_path('Helper') . '/' . $helper_name . '.php';
        } else {
            require_once __DIR__ . '/../Helper/' . $helper_name . '.php';
        }

        // 添加到属性
        $this->__helperInstance[$helper_name] = 1;
        return true;
    }

}