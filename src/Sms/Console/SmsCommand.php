<?php

namespace JiaLeo\Laravel\Sms\Console;

use Illuminate\Console\Command;

class SmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:sms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scaffold basic sms and config';

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
        //logic
        $logic_file = app_path('Logic/SmsLogic.php');
        if (file_exists($logic_file)) {
            $this->error($logic_file . '文件已存在!');
        }else{
            $logic_template = file_get_contents(__DIR__ . '/stubs/SmsLogic.stub');
            if(file_put_contents($logic_file,$logic_template)){
                $this->info('添加'.$logic_file.'成功');
            }else{
                $this->error('添加'.$logic_file.'失败');
            }
        }

        //config
        $config_file = config_path('sms.php');
        if (file_exists($config_file)) {
            $this->error($config_file . '文件已存在!');
        }else{
            $config_template = file_get_contents(__DIR__ . '/stubs/config.stub');
            if(file_put_contents($config_file,$config_template)){
                $this->info('添加'.$config_file.'成功');
            }else{
                $this->error('添加'.$config_file.'失败');
            }
        }

        $this->info('sms模块生成完毕!');
    }

}
