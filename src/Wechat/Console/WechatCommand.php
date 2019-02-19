<?php

namespace JiaLeo\Laravel\Wechat\Console;

use Illuminate\Console\Command;

class WechatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:wechat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic wechat controller';

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
        $controller_file = app_path('Http/Controllers/Api/WechatController.php');
        if (file_exists($controller_file)) {
            $this->error($controller_file . '文件已存在!');
            return;
        }

        //create controller
        $controller_template = file_get_contents(__DIR__ . '/stubs/WechatController.stub');
        if(!file_put_contents($controller_file,$controller_template)){
            $this->error('添加WechatController失败');
            return ;
        }

        $this->info('wechat模块生成完毕!');
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
