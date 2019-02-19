<?php
namespace JiaLeo\Laravel\Swoole\Console;

use Illuminate\Console\Command;

class CreateSwoole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create:swoole and loading swoole';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        load_helper('File');

        // 添加配置文件
        $template_config = file_get_contents(dirname(__FILE__) . './../Stubs/config.stub');
        $file_path = config_path() . '/swoole.php';
        //判断文件是否存在
        if (file_exists($file_path)) {
            $this->error('文件' . $file_path . ' 已存在,已跳过复制!');
        } else {
            dir_exists(dirname($file_path));
            if (file_put_contents($file_path, $template_config)) {
                $this->info($file_path . '添加swoole配置文件成功!');
            } else {
                $this->error($file_path . '添加swoole配置文件失败!');
                exit;
            }
        }

        // 添加回调事件类文件
        $template_logic = file_get_contents(dirname(__FILE__) . './../Stubs/Events.stub');
        $file_path = app_path() . '/Socket/Events.php';
        //判断文件是否存在
        if (file_exists($file_path)) {
            $this->error('文件' . $file_path . ' 已存在,已跳过复制!');
        } else {
            dir_exists(dirname($file_path));
            if (file_put_contents($file_path, $template_logic)) {
                $this->info($file_path . '添加swoole回调文件成功!');
            } else {
                $this->error($file_path . '添加swoole回调文件失败!');
                exit;
            }
        }

        // 添加packetsetting
        $template_logic = file_get_contents(dirname(__FILE__) . './../Stubs/Setting.stub');
        $file_path = app_path() . '/Socket/Setting.php';
        //判断文件是否存在
        if (file_exists($file_path)) {
            $this->error('文件' . $file_path . ' 已存在,已跳过复制!');
        } else {
            dir_exists(dirname($file_path));
            if (file_put_contents($file_path, $template_logic)) {
                $this->info($file_path . '添加swoole回调文件成功!');
            } else {
                $this->error($file_path . '添加swoole回调文件失败!');
                exit;
            }
        }

        // 添加逻辑文件
        $template_logic = file_get_contents(dirname(__FILE__) . './../Stubs/SocketLogic.stub');
        $file_path = app_path() . '/Logic/SocketLogic.php';
        //判断文件是否存在
        if (file_exists($file_path)) {
            $this->error('文件' . $file_path . ' 已存在,已跳过复制!');
        } else {
            dir_exists(dirname($file_path));
            if (file_put_contents($file_path, $template_logic)) {
                $this->info($file_path . '添加swoole逻辑文件成功!');
            } else {
                $this->error($file_path . '添加swoole逻辑文件失败!');
                exit;
            }
        }

        // 添加调试html文件
        $template_logic = file_get_contents(dirname(__FILE__) . './../Stubs/sockettester.html');
        $file_path = app_path() . '/Socket/sockettester.html';
        //判断文件是否存在
        if (file_exists($file_path)) {
            $this->error('文件' . $file_path . ' 已存在,已跳过复制!');
        } else {
            dir_exists(dirname($file_path));
            if (file_put_contents($file_path, $template_logic)) {
                $this->info($file_path . '添加swoole调试文件成功!');
            } else {
                $this->error($file_path . '添加swoole调试文件失败!');
                exit;
            }
        }

    }
}
