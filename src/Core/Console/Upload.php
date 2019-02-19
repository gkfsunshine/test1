<?php

namespace JiaLeo\Laravel\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Schema\Blueprint;

class Upload extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:upload';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic upload and routes';

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
        $this->createDirectories();

        //生成路由
        $result = file_put_contents(
            base_path('routes/api.php'),
            file_get_contents(__DIR__ . '/stubs/upload/routes.stub'),
            FILE_APPEND
        );
        if (!$result) {
            $this->error('添加路由文件失败!');
        } else {
            $this->info('添加路由文件成功!');
        }


        //生成控制器
        $controller_file = app_path('Http/Controllers/Admin/UploadController.php');
        if (file_exists($controller_file)) {
            $this->error($controller_file . '文件已存在!');
        } else {
            $result = file_put_contents(
                $controller_file,
                file_get_contents(__DIR__ . '/stubs/upload/UploadController.stub')
            );
            if (!$result) {
                $this->error('生成控制器 ' . $controller_file . '文件失败!');
            } else {
                $this->info('生成控制器 ' . $controller_file . '文件成功!');
            }
        }

        //生成model
        $model_file = app_path('Model/UploadModel.php');
        if (file_exists($model_file)) {
            $this->error($model_file . '文件已存在!');
        } else {
            $result = file_put_contents(
                $model_file,
                file_get_contents(__DIR__ . '/stubs/upload/UploadModel.stub')
            );

            if (!$result) {
                $this->error('生成model ' . $model_file . '文件失败!');
            } else {
                $this->info('生成model ' . $model_file . '文件成功!');
            }
        }


        //migrate
        //先存放到临时文件夹
        $dist = 'storage/migrations/' . date('YmdHis');
        $dist_path = base_path($dist);
        dir_exists($dist_path);
        $is_copy=copy_dir(__DIR__ . '/database/upload', $dist_path);

        if(!$is_copy){
            $this->error('复制临时文件失败,请确保storage目录有权限!');
            return;
        }

        $this->call('migrate', [
            '--path' => $dist
        ]);

        //删除文件夹
        $is_del=del_dir($dist_path);
        if(!$is_del){
            $this->error('删除临时文件失败,请自行删除!'.$dist_path);
            return;
        }

        $this->info('upload模块生成完毕!');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    public function createDirectories()
    {
        load_helper('File');
        file_exists(app_path('Http/Controllers/Admin'));

    }

}
