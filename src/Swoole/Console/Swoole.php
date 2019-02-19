<?php

namespace JiaLeo\Laravel\Swoole\Console;

use Illuminate\Console\Command;

class Swoole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'swoole {action=start}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'run swoole';

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
        $actioin = $this->argument('action');
        switch ($actioin) {
            case 'start':
                $this->start();
                break;
            case 'kill':
                //$this->kill();
                break;
            case 'reload':
                //$this->reload();
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
        //register
        $register_process = new \swoole_process(function (\swoole_process $worker) {
            $worker->exec('/usr/bin/php', array(
                base_path('artisan'),
                'swoole:register',
                'start',
                '--d',
                '--log_level=info'));
        });
        $pid = $register_process->start();
        $this->info('已启动register : ' . $pid);

        //gateway
        $gateway_process = new \swoole_process(function (\swoole_process $worker) {
            $worker->exec('/usr/bin/php', array(
                base_path('artisan'),
                'swoole:gateway',
                'start',
                '--d',
                '--log_level=info'));
        });
        $pid = $gateway_process->start();
        $this->info('已启动gateway : ' . $pid);

        //worker
        $worker_process = new \swoole_process(function (\swoole_process $worker) {
            $worker->exec('/usr/bin/php', array(
                base_path('artisan'),
                'swoole:worker',
                'start',
                '--d',
                '--log_level=info'));
        });
        $pid = $worker_process->start();
        $this->info('已启动worker : ' . $pid);

        \swoole_process::wait();
    }

    /**
     * 启动
     */
    public function stop()
    {
        //register
        $register_process = new \swoole_process(function (\swoole_process $worker) {
            $worker->exec('/usr/bin/php', array(
                base_path('artisan'),
                'swoole:register',
                'stop'));
        });
        $pid = $register_process->start();
        $this->info('已发送关闭register请求 : ' . $pid);

        //gateway
        $gateway_process = new \swoole_process(function (\swoole_process $worker) {
            $worker->exec('/usr/bin/php', array(
                base_path('artisan'),
                'swoole:gateway',
                'stop'));
        });
        $pid = $gateway_process->start();
        $this->info('已发送关闭gateway请求 : ' . $pid);

        //worker
        $worker_process = new \swoole_process(function (\swoole_process $worker) {
            $worker->exec('/usr/bin/php', array(
                base_path('artisan'),
                'swoole:worker',
                'stop'));
        });
        $pid = $worker_process->start();
        $this->info('已发送关闭worker请求 : ' . $pid);

        \swoole_process::wait();
    }
}
