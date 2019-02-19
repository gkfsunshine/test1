<?php

namespace JiaLeo\Laravel\Opcache\Console;

use Illuminate\Console\Command;
use JiaLeo\Laravel\Opcache\OpcacheClass;
use Illuminate\Support\Facades\Crypt as Crypt;

class Clear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opcache:clear {action=default}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the opcode cache';

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

        $action = $this->argument('action');

        //cli清除
        if ($action == 'default' || $action == 'cli') {
            $res = OpcacheClass::clear();
            if (!$res) {
                $this->line('Cil Opcode cache: Nothing to clear');
            } else {
                $this->info('Cil Opcode cache cleared');
            }
        }

        //web清除
        if ($action == 'default' || $action == 'web') {
            load_helper('Network');

            try {
                $host = env('OPCACHE_URL', config('app.url'));
                $key = Crypt::encrypt('opcache');
                $url = $host . '/opcache/clear?key=' . $key;
                $res = http_get($url);
                $res = json_decode($res, true);
                if (!$res['result']) {
                    $this->line('Web Opcode cache: Nothing to clear');
                } else {
                    $this->info('Web Opcode cache cleared');
                }
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

    }

}
