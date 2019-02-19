<?php

namespace JiaLeo\Laravel\Swoole\Console;

use Illuminate\Console\Command;

class Register extends Command
{
    /**
     * The name and signature of the console command.
     *
     * var string
     */
    protected $signature = 'swoole:register {action=start}
                            {--d : Set daemonize}
                            {--log_level= : Set the log level}';

    /**
     *
     * The console command description.
     *
     * var string
     */
    protected $description = 'the swoole server for register';

    protected $pidFile;
    protected $logFile;

    /**
     * Create a new command instance.
     *
     * return void
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

        $this->pidFile = storage_path('swoole/' . config('app.name') . '_register.pid');
        $this->logFile = storage_path('swoole/' . config('app.name') . '_register.log');

        $actioin = $this->argument('action');
        switch ($actioin) {
            case 'start':
                $this->start();
                break;
            case 'kill':
                $this->kill();
                break;
            case 'restart':
                $this->restart();
                break;
            case 'reload':
                $this->reload();
                break;
            case 'stop':
                $this->stop();
                break;
            default:
                break;
        }
    }

    /**
     * start the register server
     */
    private function start()
    {
        $options = $this->options();
        //是否以守护进程启动
        $daemonize = $options['d'];
        $log_level = $options['log_level'];

        //判断是否已开启
        if (file_exists($this->pidFile)) {
            $pid = file_get_contents($this->pidFile);
            $is_running = \swoole_process::kill($pid, 0);
            //判断是否真的存在
            if ($is_running) {
                $this->error('程序已开启!');
                exit;
            }
        }

        $config = config('swoole.register');
        $server = new \JiaLeo\Swoole\Lib\Register($config['host'], $config['port']);
        $server->daemonize = $daemonize;
        $server->project_name = config('app.name');

        $server->is_gateway_report = $config['is_gateway_report'];
        $server->is_worker_report = $config['is_worker_report'];
        $server->is_register_report = $config['is_register_report'];
        $server->report_driver = $config['report_driver'];

        if ($config['is_register_report']) {
            $server->report_redis_host = env('REDIS_HOST');
            $server->report_redis_port = env('REDIS_PORT');
            $server->report_redis_auth = env('REDIS_PASSWORD');
            $server->report_redis_db = env('REDIS_DATABASE');
        }

        load_helper('File');

        //日志路径
        if (dir_exists(dirname($this->logFile)) === false) {
            $this->error('日志路径配置:' . $this->logFile . ' 写入错误!');
            exit;
        }

        $server->log_file = $this->logFile;

        //日志等级
        if ($log_level !== null) {
            $server->log_level = $log_level;
        }

        //pid文件
        if (dir_exists(dirname($this->pidFile)) === false) {
            $this->error('日志路径配置:' . $this->pidFile . ' 写入错误!');
            exit;
        }
        $server->pid_file = $this->pidFile;

        //gateway_id文件
        $gateway_id_file = storage_path('swoole/') . config('app.name') . '_gateway_id';
        if (dir_exists(dirname($gateway_id_file)) === false) {
            $this->error('日志路径配置:' . $gateway_id_file . ' 写入错误!');
            exit;
        }
        $server->gateway_id_file = $gateway_id_file;

        $server->start();
    }

    /**
     * 安全终止服务
     */
    public function stop()
    {
        $this->signal(SIGTERM);
        $this->info('发送关闭信号成功!');

        //检测是否真正关闭
        $this->info('正在确认是否真正关闭');
        $i = 1;
        while (true) {
            if ($i == 100) {
                echo "\n";
                $this->error('关闭失败!');
                exit;
            }
            if (!file_exists($this->pidFile)) {
                echo "\n";
                $this->info('关闭成功!');
                exit;
            }
            echo '.';
            sleep(1);
            $i++;
        }
    }

    /**
     * 平稳重启worker
     */
    public function reload()
    {
        $this->signal(SIGUSR1);
        $this->info('平稳reload Worker成功!');
    }

    /**
     * 安全终止服务
     */
    public function restart()
    {

        $this->signal(SIGTERM);
        $this->info('发送关闭信号成功!');

        //检测是否真正关闭
        $i = 1;
        while (true) {
            if ($i == 30) {
                echo "\n";
                $this->error('关闭失败!');
                exit;
            }
            if (!file_exists($this->pidFile)) {
                echo "\n";
                $this->info('关闭成功!');
                break;
            }
            echo '.';
            sleep(1);
            $i++;
        }

        //重新启动
        $daemonize = $this->confirm('设置为守护进程?');
        $this->input->setOption('d', $daemonize);

        $log_level = $this->choice('设置log_level', ['debug', 'info'], 1);
        $this->input->setOption('log_level', $log_level);
        $this->start();
    }

    /**
     * 强制关闭
     */
    public function kill()
    {
        $project_name = config('app.name');
        $string = '$(ps aux|grep ' . $project_name . '_Swoole_Register' . '|grep -v grep|awk ' . '\'{print $2}\'' . ')';

        if (!function_exists('shell_exec')) {
            $this->error("no support exec");
        }
        shell_exec('kill -9 ' . $string);
    }

    /**
     * send signal to register server process
     * @param $signal -9 SIGKILL 强制杀掉进程
     *                -10 SIGUSR1 向主进程/管理进程发送SIGUSR1信号，将平稳地restart所有worker进程
     *                -12 SIGUSR2 重启所有task_worker进程
     *                -15 SIGTERM 向主进程/管理进程发送此信号服务器将安全终止
     *                -34 SIGRTMIN 重新打开日志文件
     */
    private function signal($signal = 15)
    {
        //获取pid文件
        if (!file_exists($this->pidFile)) {
            $this->error('pid文件不存在,请确认程序是否已开启!');
            exit;
        }

        $pid = file_get_contents($this->pidFile);
        if (empty($pid)) {
            $this->error('获取pid错误!');
            exit;
        }

        try {
            \swoole_process::kill(intval($pid), $signal);
            return true;
        } catch (\Exception $e) {
            $this->error('错误:' . $e->getMessage());
        }
    }

}
