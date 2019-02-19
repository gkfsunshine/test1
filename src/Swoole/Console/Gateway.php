<?php

namespace JiaLeo\Laravel\Swoole\Console;

use Illuminate\Console\Command;

class Gateway extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole:gateway {action=start}  {param=gateway-001}
                            {--d : Set daemonize}
                            {--log_level= : Set the log level}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run swoole for websocket';

    protected $logPath;
    protected $logFilename;
    protected $pidPath;
    protected $pidFilename;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $param = $this->argument('param');
        $this->logPath = $this->pidPath = storage_path('swoole');
        $this->logFilename = config('app.name') . '_' . $param . '.log';
        $this->pidFilename = config('app.name') . '_' . $param . '.pid';

        //日志和pid路径
        load_helper('File');
        if (dir_exists(dirname($this->logPath)) === false) {
            $this->error('日志路径配置:' . $this->logPath . ' 写入错误!');
            exit;
        }

        $actioin = $this->argument('action');
        switch ($actioin) {
            case 'start':
                $this->start();
                break;
            case 'kill':
                $this->kill();
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
     * 启动
     */
    public function start()
    {
        $options = $this->options();

        //判断是否已开启
        if (file_exists($this->pidPath . '/' . $this->pidFilename)) {
            $pid = file_get_contents($this->pidPath . '/' . $this->pidFilename);
            $is_running = \swoole_process::kill($pid, 0);
            //判断是否真的存在
            if ($is_running) {
                $this->error('程序已开启!');
                exit;
            }
        }

        $key = $this->argument('param');
        $gateway_config = config('swoole.gateway')[$key];
        $register_config = config('swoole.register');
        $gateway_config['verify_websocket_class'] = config('swoole.gateway.verify_websocket_class');
        $gateway_config['packet_setting_class'] = config('swoole.gateway.packet_setting_class');

        $server = new \JiaLeo\Swoole\Lib\Gateway($gateway_config, $register_config);
        $server->daemonize = boolval($options['d']);             //是否以守护进程启动
        $server->log_path = $this->logPath;
        $server->log_filename = $this->logFilename;
        $server->pid_path = $this->pidPath;
        $server->pid_filename = $this->pidFilename;
        $server->project_name = config('app.name');
        if (!empty($options['log_level'])) {
            $server->log_level = $options['log_level'];
        }

        $server->report_redis_host = env('REDIS_HOST');
        $server->report_redis_port = env('REDIS_PORT');
        $server->report_redis_auth = env('REDIS_PASSWORD');
        $server->report_redis_db = env('REDIS_DATABASE');

        $server->start();
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
    public function stop()
    {
        $this->signal(SIGTERM);
        $this->info('发送关闭信号成功!');

        $param = $this->argument('param');

        //检测是否真正关闭
        $i = 1;
        $this->info('正在确认是否真正关闭');
        while (true) {
            if ($i == 100) {
                echo "\n";
                $this->error('关闭失败!');
                exit;
            }
            if (!file_exists($this->pidPath . '/' . $this->pidFilename)) {
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
     * 强制关闭
     */
    public function kill()
    {
        $project_name = config('app.name');
        $string = '$(ps aux|grep ' . $project_name . '_Swoole_Gateway' . '|grep -v grep|awk ' . '\'{print $2}\'' . ')';

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
        $pid_path = $this->pidPath . '/' . $this->pidFilename;
        //获取pid文件
        if (!file_exists($pid_path)) {
            $this->error('pid文件不存在,请确认程序是否已开启!');
            exit;
        }

        $pid = file_get_contents($pid_path);
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
