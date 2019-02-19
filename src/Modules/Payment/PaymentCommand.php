<?php

namespace JiaLeo\Laravel\Modules\Payment;

use Illuminate\Console\Command;

class PaymentCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module:payment {action}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create payment module';

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
        $actioin = $this->argument('action');

        switch ($actioin) {
            //支付初始化
            case 'init':

                //配置文件
                $this->createConfig();

                //Controller文件
                $this->createController();

                //Logic文件
                $this->createLogic();

                //Model
                $this->createModel();

                //datebase
                $this->createMigrate();

                //router
                $this->createRoute();

                if(!class_exists(\JiaLeo\Payment\Wechatpay\BasePay::class)){
                    $this->warn('payment扩展包还没有安装!');
                    $this->warn('********************  请在终端运行命令  ****************');
                    $this->warn('* composer require jialeo/payment ');
                    $this->warn('******************************************************');
                }

                break;
        }

    }

    /**
     * 生成配置文件
     */
    private function createConfig()
    {
        //config
        $config_file = config_path('payment.php');
        if (file_exists($config_file)) {
            $this->error($config_file . '文件已存在!');
        } else {
            $config_template = file_get_contents(__DIR__ . '/stubs/configs/payment.stub');
            if (file_put_contents($config_file, $config_template)) {
                $this->info('添加' . $config_file . '成功');
            } else {
                $this->error('添加' . $config_file . '失败');
            }
        }
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
        $is_copy = copy_dir(__DIR__ . '/stubs/databases', $dist_path);

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
        $is_copy = copy_stubs(__DIR__ . '/stubs/models', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建model--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        $this->info('添加Model成功!');
    }

    /**
     * 生成controller
     * @return bool
     */
    private function createController()
    {
        $dist_path = app_path('Http/Controllers/Pay');
        $is_copy = copy_stubs(__DIR__ . '/stubs/controllers', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建controller--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        $this->info('添加Controller成功!');
    }

    /**
     * 生成Logic
     * @return bool
     */
    private function createLogic()
    {
        $dist_path = app_path('Logic/Pay');
        $is_copy = copy_stubs(__DIR__ . '/stubs/logics', $dist_path, true);
        if (!$is_copy) {
            $this->error('创建logic--复制文件失败,请确保' . dirname($dist_path) . '目录有权限!');
            return false;
        }

        $this->info('添加Logic成功!');
    }

    /**
     * 生成router
     * @return bool
     */
    private function createRoute()
    {

        //route
        $route_file = base_path('routes/pay.php');
        if (file_exists($route_file)) {
            $this->error($route_file . '文件已存在!');
        } else {
            $route_template = file_get_contents(__DIR__ . '/stubs/routes/pay.stub');
            if (file_put_contents($route_file, $route_template)) {
                $this->info('添加' . $route_file . '成功');
            } else {
                $this->error('添加' . $route_file . '失败');
            }
        }

        return true;
    }
}
