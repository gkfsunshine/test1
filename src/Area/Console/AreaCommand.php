<?php

namespace JiaLeo\Laravel\Area\Console;

use Illuminate\Console\Command;

class AreaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:area';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic area module';

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

        $this->warn('正在操作数据库,可能需要数十秒或数分钟,请耐心稍后...');

        //migrate
        $this->createMigrate();

        //model
        if ($this->createModel()) {
            $this->info('生成model成功!');
        }

        //controller
        if ($this->createController()) {
            $this->info('生成controller成功!');
        }

        //createLogic
        if ($this->createLogic()) {
            $this->info('生成logic成功!');
        }

        $this->info('Area模块生成成功!');
    }

    /**
     * 创建migrate
     */
    private function createMigrate()
    {
        //先存放到临时文件夹
        $dist = 'storage/migrations/' . date('YmdHis');
        $dist_path = base_path($dist);
        dir_exists($dist_path);
        $is_copy = copy_dir(__DIR__ . '/database', $dist_path);

        if (!$is_copy) {
            $this->error('创建migrate--复制临时文件失败,请确保storage目录有权限!');
            return false;
        }

        $this->call('migrate', [
            '--path' => $dist
        ]);

        //删除文件夹
        $is_del = del_dir($dist_path);
        if (!$is_del) {
            $this->error('创建migrate--删除临时文件失败,请自行删除!' . $dist_path);
            return false;
        }

        return true;
    }

    /**
     * 生成model
     * @return bool
     */
    private function createModel()
    {
        $dist_path = app_path('Model/AreaModel.php');
        if (file_exists($dist_path)) {
            $this->error('model文件已存在!');
            return false;
        }

        $is_copy = file_put_contents(
            $dist_path,
            file_get_contents(__DIR__ . '/stubs/AreaModel.stub')
        );
        if (!$is_copy) {
            $this->error('创建model--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 生成controller
     * @return bool
     */
    private function createController()
    {
        $dist_path = app_path('Http/Controllers/Api/AreaController.php');
        if (file_exists($dist_path)) {
            $this->error('controller文件已存在!');
            return false;
        }

        $is_copy = file_put_contents(
            $dist_path,
            file_get_contents(__DIR__ . '/stubs/AreaController.stub')
        );
        if (!$is_copy) {
            $this->error('创建controller--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 生成Logic
     * @return bool
     */
    private function createLogic()
    {
        $dist_path = app_path('Logic/AreaLogic.php');
        if (file_exists($dist_path)) {
            $this->error('logic文件已存在!');
            return false;
        }

        $is_copy = file_put_contents(
            $dist_path,
            file_get_contents(__DIR__ . '/stubs/AreaLogic.stub')
        );
        if (!$is_copy) {
            $this->error('创建logic--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }
}
