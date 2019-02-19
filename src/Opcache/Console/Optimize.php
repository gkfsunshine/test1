<?php

namespace JiaLeo\Laravel\Opcache\Console;

use Illuminate\Console\Command;
use JiaLeo\Laravel\Opcache\OpcacheClass;
use Illuminate\Support\Facades\Crypt as Crypt;

class Optimize extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'opcache:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Pre-compile your application code';

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

        load_helper('Network');
        try {
            $host = env('OPCACHE_URL', config('app.url'));
            $key = Crypt::encrypt('opcache');
            $url = $host . '/opcache/optimize?key=' . $key;
            $res = http_get($url);
            $res = json_decode($res, true);
            if (!$res['result']) {
                $this->error('No OPcache information available');
            } else {
                $this->line('Web Optimize started, this can take a while...');
                $this->info(sprintf('%s of %s files optimized', $res['result']['compiled_count'], $res['result']['total_files_count']));
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

    }

}
