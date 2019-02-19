<?php

namespace JiaLeo\Laravel\Wechat\Console;

use Illuminate\Console\Command;

class QiyeWechatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:qiyewechat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic qiyewechat controller';

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
     */
    public function handle()
    {
        $this->createDirectories();

        //controller
        $controller_file = app_path('Http/Controllers/Api/QiyeWechatController.php');
        if (file_exists($controller_file)) {
            $this->error($controller_file . '文件已存在!');
            return;
        }

        //create controller
        $controller_template = file_get_contents(__DIR__ . '/stubs/QiyeWechatController.stub');
        if(!file_put_contents($controller_file,$controller_template)){
            $this->error('添加QiyeWechatController失败');
            return ;
        }

        //判断是否需要生成数据库表
        if($this->confirm('需要生成企业微信授权数据库表吗?[y|n]')){
            $this->call('create:qiyewechat:table');
        }

        $this->info('qiyewechat模块生成完毕!');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    public function createDirectories()
    {
        load_helper('File');
        file_exists(app_path('Http/Controllers/Api'));

    }

}
