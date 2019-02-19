<?php

namespace JiaLeo\Laravel\Core\Console;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class CreateInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Init App';

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
        $this->alert('即将初始化项目...');

        if (!$this->confirm('确认立刻对项目进行初始化?', false)) {
            $this->warn('已退出初始化!');
            exit;
        }

        load_helper('File');

        //migrate
        if ($this->createMigrate()) {
            $this->info('migrate成功!');
        }

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

        //routes
        if ($this->createRoute()) {
            $this->info('覆盖路由成功!');
        }

        //mail
        if ($this->createMail()) {
            $this->info('生成mail成功');
        }

        //view
        if ($this->createViews()) {
            $this->info('生成view成功');
        }
        $this->line('');

        //生成短信模块
        $this->alert('开始生成短信模块');
        $this->call('create:sms');
        $this->call('module:sms');
        $this->line('');

        //生成微信模块
        $this->alert('开始生成微信模块');
        $this->call('create:wechat');
        $this->line('');

        //生成area模块
        $this->alert('开始生成Area模块');
        $this->call('create:area');
        $this->line('');

        //生成key
        $this->alert('开始生成Application key');
        $this->call('key:generate');
        $this->line('');

        //生成权限
        $this->alert('开始生成管理后台权限');
        $process = new Process(
            'php artisan create:permission', base_path(), null, null, null
        );
        $process->run();

        echo $process->getOutput();

        $this->line('');
        $this->info('**********************************');
        $this->info('*   初始化成功! Enjoy~   *');
        $this->info('**********************************');
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
        $is_copy = copy_dir(__DIR__ . '/database/init', $dist_path);

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
        $dist_path = app_path('Model');
        $is_copy = copy_stubs(__DIR__ . '/stubs/init/model', $dist_path, true);
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
        $dist_path = app_path('Http/Controllers');
        $is_copy = copy_stubs(__DIR__ . '/stubs/init/controller', $dist_path, true);
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
        $dist_path = app_path('Logic');
        $is_copy = copy_stubs(__DIR__ . '/stubs/init/logic', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建logic--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 生成route
     * @return bool
     */
    private function createRoute()
    {
        $dist_path = base_path('routes');
        $is_copy = copy_stubs(__DIR__ . '/stubs/init/route', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建routes--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 生成mail
     * @return bool
     */
    private function createMail()
    {
        $dist_path = app_path('Mail');
        $is_copy = copy_stubs(__DIR__ . '/stubs/init/mail', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建mail--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }

    /**
     * 生成view
     * @return bool
     */
    private function createViews()
    {
        $dist_path = base_path('resources/views');
        $is_copy = copy_stubs(__DIR__ . '/stubs/init/views', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建view--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        return true;
    }
}
